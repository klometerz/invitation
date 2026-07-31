<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Guest;
use App\Models\Invitation; // Load Model Baru
use Inertia\Inertia;

class WeddingController extends Controller
{
    /**
     * Tampilkan halaman undangan berdasarkan slug
     */
    public function index(Request $request, $slug = 'maulia-kahfi') 
    {
        // 1. Cari Undangan berdasarkan Slug
        // Eager load theme biar kita tau folder svelte mana yang mau di-render
        $invitation = Invitation::with('theme')
            ->where('slug', $slug)
            ->firstOrFail();

        // 2. Logic Data Tamu (Tetap lo pertahankan buat fitur RSVP)
        $guestCode = $request->query('guest');
        // Pastiin tamu yang dicari emang terdaftar di undangan ini
        $guest = Guest::where('invitation_id', $invitation->id)
            ->where('guest_code', $guestCode)
            ->first();

        // 3. Render ke Svelte secara dinamis
        // Kita kirim data JSON 'content' ke frontend
        return Inertia::render('Wedding', [
            'invitation' => $invitation,
            'content' => $invitation->content, // Ini data JSON dari Filament
            'guestData' => $guest ? [
                'name' => $guest->name,
                'code' => $guest->guest_code,
                'city' => $guest->city,
            ] : null,
        ]);
    }
}