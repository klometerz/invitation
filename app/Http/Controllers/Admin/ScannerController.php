<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
        abort_if($request->user() === null, 403);

        $request->validate([
            'code' => 'required|string|max:255',
        ]);

        // Scoped by TenantScope: staff of A can never scan B's guest.
        $guest = Guest::where('guest_code', $request->code)->first();

        // QR code tidak ditemukan di database
        if (! $guest) {
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

        // Sukses check-in.
        // K8: the operator is ALWAYS the authenticated user, never a value
        // taken from the request body.
        $guest->update([
            'checkin_status' => 'arrived',
            'checked_in_at' => now(),
            'actual_guest_count' => $guest->quota, // default sesuai quota, bisa diedit nanti
            'checked_in_by' => $request->user()->name,
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

    public function todayStats()
    {
        $count = Guest::where('checkin_status', 'arrived')
            ->whereDate('checked_in_at', Carbon::today())
            ->count();

        return response()->json(['count' => $count]);
    }
}
