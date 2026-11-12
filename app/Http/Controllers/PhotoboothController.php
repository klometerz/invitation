<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\PhotoboothPhoto;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PhotoboothController extends Controller
{
    /** Max accepted base64 string (includes the data URL prefix). */
    private const MAX_BASE64_LENGTH = 4_000_000;

    /** Max decoded image size (~2.5 MB). */
    private const MAX_BYTES = 2_500_000;

    /** Max width/height in pixels. */
    private const MAX_DIMENSION = 4000;

    /**
     * Halaman photobooth publik untuk satu undangan.
     * The tenant was already resolved by the route middleware, so the guest
     * lookup below cannot see another invitation's guest.
     */
    public function index(Request $request)
    {
        $invitation = $request->attributes->get('invitation');
        $meta = $invitation instanceof \App\Models\Invitation
            ? \App\Support\InvitationPresenter::for($invitation)->toArray()
            : null;

        $guestCode = $request->query('guest');
        $guest = $guestCode ? Guest::where('guest_code', $guestCode)->first() : null;

        if (! $guest) {
            return Inertia::render('Photobooth', [
                'accessGranted' => false,
                'reason' => 'invalid',
                'meta' => $meta,
            ]);
        }

        if ($guest->checkin_status !== 'arrived') {
            return Inertia::render('Photobooth', [
                'accessGranted' => false,
                'reason' => 'not_checked_in',
                'guestName' => $guest->name,
                'meta' => $meta,
            ]);
        }

        return Inertia::render('Photobooth', [
            'accessGranted' => true,
            'guestData' => [
                'name' => $guest->name,
                'guest_code' => $guest->guest_code,
            ],
            'meta' => $meta,
        ]);
    }

    /**
     * Legacy `/photobooth?guest=CODE` link (already printed on QR cards).
     * Resolves the tenant from the guest, then redirects to the slug URL.
     */
    public function legacyRedirect(Request $request)
    {
        $code = $request->query('guest');

        if (! is_string($code) || $code === '') {
            abort(404);
        }

        // Tenant unknown here: this lookup exists only to discover it.
        $guest = app(TenantContext::class)->withoutScope(
            fn () => Guest::where('guest_code', $code)->first()
        );

        if ($guest === null || $guest->invitation_id === null) {
            abort(404);
        }

        $invitation = \App\Models\Invitation::find($guest->invitation_id);

        if ($invitation === null) {
            abort(404);
        }

        return redirect()->to(
            url('/'.$invitation->slug.'/photobooth').'?guest='.urlencode($code)
        );
    }

    public function adminIndex(Request $request)
    {
        // Scoped automatically.
        $query = PhotoboothPhoto::with('guest')->latest();

        if ($request->filled('search')) {
            $query->whereHas('guest', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%');
            });
        }

        $photos = $query->paginate(24)->withQueryString();

        $photos->getCollection()->transform(function ($photo) {
            return [
                'id' => $photo->id,
                'image_path' => $photo->image_path,
                'message' => $photo->message,
                'guest_name' => $photo->guest?->name ?? 'Tamu Tidak Diketahui',
                'created_at' => $photo->created_at->format('d M Y, H:i'),
            ];
        });

        return Inertia::render('Admin/PhotoboothIndex', [
            'photos' => $photos,
            'total' => PhotoboothPhoto::count(),
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Simpan hasil foto dari canvas (base64) + pesan opsional
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'guest_code' => 'required|string',
            'image' => 'required|string',
            'message' => 'nullable|string|max:500',
        ]);

        // Scoped: a code from another invitation is simply not found.
        $guest = Guest::where('guest_code', $validated['guest_code'])->first();

        // Re-checked on the server: never trust the frontend.
        if (! $guest || $guest->checkin_status !== 'arrived') {
            return response()->json([
                'message' => 'Akses ditolak. Anda belum check-in.',
            ], 403);
        }

        $invitation = \App\Models\Invitation::findOrFail(app(TenantContext::class)->require());

        // P3-T3: per-guest capture limit.
        $photoLimit = $invitation->limit('max_photobooth_per_guest');

        if ($photoLimit !== null) {
            $taken = PhotoboothPhoto::where('guest_id', $guest->id)->count();

            if ($taken >= $photoLimit) {
                return response()->json([
                    'message' => "Batas {$photoLimit} foto per tamu sudah tercapai.",
                ], 422);
            }
        }

        // P3-T4 (K7): cap the payload BEFORE decoding it.
        if (strlen($validated['image']) > self::MAX_BASE64_LENGTH) {
            return response()->json(['message' => 'Ukuran gambar terlalu besar.'], 422);
        }

        // Only real image data URLs are accepted.
        if (! preg_match('/^data:image\/(png|jpe?g|webp);base64,/', $validated['image'])) {
            return response()->json(['message' => 'Format gambar tidak valid.'], 422);
        }

        $raw = base64_decode(substr($validated['image'], strpos($validated['image'], ',') + 1), true);

        if ($raw === false || $raw === '') {
            return response()->json(['message' => 'Data gambar rusak.'], 422);
        }

        if (strlen($raw) > self::MAX_BYTES) {
            return response()->json(['message' => 'Ukuran gambar terlalu besar.'], 422);
        }

        $info = @getimagesizefromstring($raw);

        if ($info === false) {
            return response()->json(['message' => 'Berkas bukan gambar yang valid.'], 422);
        }

        [$width, $height] = $info;

        if ($width < 1 || $height < 1 || $width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            return response()->json(['message' => 'Dimensi gambar tidak didukung.'], 422);
        }

        $source = @imagecreatefromstring($raw);

        if ($source === false) {
            return response()->json(['message' => 'Gambar tidak dapat diproses.'], 422);
        }

        // Re-encode to JPEG on a white canvas: strips metadata and any
        // embedded payload, and makes transparency safe.
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
        imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 85);
        $jpeg = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        if (! is_string($jpeg) || $jpeg === '') {
            return response()->json(['message' => 'Gambar tidak dapat disimpan.'], 422);
        }

        $invitationId = $invitation->id;
        $filename = "invitations/{$invitationId}/photobooth/".Str::random(20).'.jpg';
        Storage::disk('public')->put($filename, $jpeg);

        $photo = PhotoboothPhoto::create([
            'invitation_id' => $invitationId,
            'guest_id' => $guest->id,
            'image_path' => Storage::url($filename),
            'message' => $validated['message'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'photo' => [
                'id' => $photo->id,
                'image_path' => $photo->image_path,
            ],
        ]);
    }

    public function destroy($id)
    {
        // Scoped: another tenant's id 404s.
        $photo = PhotoboothPhoto::findOrFail($id);

        // Hapus file fisik
        $relativePath = str_replace('/storage/', '', $photo->image_path);
        Storage::disk('public')->delete($relativePath);

        $photo->delete();

        return back()->with('success', 'Foto berhasil dihapus.');
    }

    public function printView(Request $request)
    {
        $invitation = \App\Models\Invitation::findOrFail(app(TenantContext::class)->require());

        // P3-T9: the memory book renders every photo into one document, so it
        // is paginated in fixed batches to keep the PDF/render bounded.
        $perPage = 200;
        $page = max(1, (int) $request->query('page', 1));

        $query = PhotoboothPhoto::with('guest')->latest();

        $total = (clone $query)->count();

        $photos = $query
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($photo) {
                return [
                    'image_path' => $photo->image_path,
                    'message' => $photo->message,
                    'guest_name' => $photo->guest?->name ?? 'Tamu',
                    'created_at' => $photo->created_at->format('d M Y'),
                ];
            });

        return Inertia::render('Admin/PhotoboothPrint', [
            'photos' => $photos,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'meta' => \App\Support\InvitationPresenter::for($invitation)->toArray(),
        ]);
    }
}
