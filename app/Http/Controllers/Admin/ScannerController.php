<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ScannerController extends Controller
{
    /**
     * Halaman scanner untuk petugas
     */
    public function index()
    {
        return Inertia::render('Admin/Scanner');
    }

    /**
     * Proses check-in tamu berdasarkan guest_code dari QR
     */
    public function checkin(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $guest = Guest::where('guest_code', $request->code)->first();

        // QR code tidak ditemukan di database
        if (!$guest) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'QR Code tidak ditemukan. Pastikan kode benar.',
            ], 404);
        }

        // VALIDASI: Sudah pernah check-in sebelumnya
        if ($guest->checkin_status === 'arrived') {
            return response()->json([
                'status' => 'duplicate',
                'message' => 'Tamu ini SUDAH check-in sebelumnya!',
                'guest' => [
                    'name' => $guest->name,
                    'city' => $guest->city,
                    'checked_in_at' => $guest->checked_in_at?->format('H:i:s'),
                    'checked_in_by' => $guest->checked_in_by,
                    'category' => $guest->category,
                ],
            ], 409); // 409 Conflict
        }

        // Sukses check-in
        $guest->update([
            'checkin_status' => 'arrived',
            'checked_in_at' => now(),
            'actual_guest_count' => $guest->quota, // default sesuai quota, bisa diedit nanti
            'checked_in_by' => $request->user()?->name ?? 'Unknown',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Check-in berhasil!',
            'guest' => [
                'name' => $guest->name,
                'city' => $guest->city,
                'category' => $guest->category,     
                'quota' => $guest->quota,
                'checked_in_at' => $guest->checked_in_at->format('H:i:s'),
            ],
        ]);
    }

    /**
     * Manual check-in via input kode (fallback kalau kamera error)
     */
    public function manualCheckin(Request $request)
    {
        return $this->checkin($request);
    }

  

public function todayStats() {
    $count = Guest::where('checkin_status', 'arrived')
        ->whereDate('checked_in_at', Carbon::today())
        ->count();

    return response()->json(['count' => $count]);
}
}