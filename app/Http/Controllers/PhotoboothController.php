<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\PhotoboothPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PhotoboothController extends Controller
{
    /**
     * Tampilkan halaman photobooth — WAJIB validasi checkin_status
     */
    // public function index(Request $request)
    // {
    //     $guestCode = $request->query('guest');
    //     $guest = $guestCode ? Guest::where('guest_code', $guestCode)->first() : null;

    //     // Guard: harus ada guest_code valid DAN sudah check-in
    //     if (!$guest) {
    //         return Inertia::render('Photobooth', [
    //             'accessGranted' => false,
    //             'reason' => 'invalid',
    //         ]);
    //     }

    //     if ($guest->checkin_status !== 'arrived') {
    //         return Inertia::render('Photobooth', [
    //             'accessGranted' => false,
    //             'reason' => 'not_checked_in',
    //             'guestName' => $guest->name,
    //         ]);
    //     }

    //     return Inertia::render('Photobooth', [
    //         'accessGranted' => true,
    //         'guestData' => [
    //             'name' => $guest->name,
    //             'guest_code' => $guest->guest_code,
    //         ],
    //     ]);
    // }


    public function adminIndex(Request $request)
{
    $query = PhotoboothPhoto::with('guest')->latest();

    if ($request->filled('search')) {
        $query->whereHas('guest', function($q) use ($request) {
            $q->where('name', 'like', '%' . $request->search . '%');
        });
    }

    $photos = $query->paginate(24)->withQueryString();

    $photos->getCollection()->transform(function($photo) {
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

        $guest = Guest::where('guest_code', $validated['guest_code'])->first();

        // Validasi ulang di server — JANGAN percaya frontend
        if (!$guest || $guest->checkin_status !== 'arrived') {
            return response()->json([
                'message' => 'Akses ditolak. Anda belum check-in.',
            ], 403);
        }

        // Decode base64 image
        $imageData = $validated['image'];
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
            $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $imageData = base64_decode($imageData);

            $filename = 'photobooth/' . Str::random(20) . '.' . $extension;
            \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $imageData);

            $photo = PhotoboothPhoto::create([
                'guest_id' => $guest->id,
                'image_path' => \Illuminate\Support\Facades\Storage::url($filename),
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
    $photo = PhotoboothPhoto::findOrFail($id);

    // Hapus file fisik
    $relativePath = str_replace('/storage/', '', $photo->image_path);
    \Illuminate\Support\Facades\Storage::disk('public')->delete($relativePath);

    $photo->delete();

    return back()->with('success', 'Foto berhasil dihapus.');
}

public function printView(Request $request)
{
    $photos = PhotoboothPhoto::with('guest')
        ->latest()
        ->get()
        ->map(function($photo) {
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
    ]);
}
}