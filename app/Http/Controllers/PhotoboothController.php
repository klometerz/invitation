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
            'image' => 'required|string', // base64 data URL
            'message' => 'nullable|string|max:500',
        ]);

        // Scoped: a code from another invitation is simply not found.
        $guest = Guest::where('guest_code', $validated['guest_code'])->first();

        // Validasi ulang di server â€” JANGAN percaya frontend
        if (! $guest || $guest->checkin_status !== 'arrived') {
            return response()->json([
                'message' => 'Akses ditolak. Anda belum check-in.',
            ], 403);
        }

        $invitationId = app(TenantContext::class)->require();

        // Decode base64 image
        $imageData = $validated['image'];
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
            $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $imageData = base64_decode($imageData);

            $filename = "invitations/{$invitationId}/photobooth/".Str::random(20).'.'.$extension;
            Storage::disk('public')->put($filename, $imageData);

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

        return response()->json(['message' => 'Format gambar tidak valid.'], 422);
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

        $photos = PhotoboothPhoto::with('guest')
            ->latest()
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
            'total' => $photos->count(),
            'meta' => \App\Support\InvitationPresenter::for($invitation)->toArray(),
        ]);
    }
}
