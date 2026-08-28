<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InvitationController extends Controller
{
    public function edit()
    {
        // Single-tenant: asumsi cuma 1 invitation (punya lo sendiri)
        $invitation = Invitation::first();

        if (!$invitation) {
            $invitation = Invitation::create([
                'title' => 'Wedding Invitation',
                'slug' => 'wedding',
                'is_active' => true,
                'content' => [
                    'hero' => ['music_url' => null, 'background_url' => null],
                    'mempelai' => [
                        'pria' => ['nama_lengkap' => '', 'nama_panggilan' => '', 'ayah' => '', 'ibu' => '', 'foto_url' => null],
                        'wanita' => ['nama_lengkap' => '', 'nama_panggilan' => '', 'ayah' => '', 'ibu' => '', 'foto_url' => null],
                    ],
                    'acara' => [],
                    'stories' => [],
                ],
            ]);
        }

        return Inertia::render('Admin/InvitationEdit', [
            'invitation' => $invitation,
        ]);
    }

    public function update(Request $request)
    {

    
        $invitation = Invitation::first();

        $content = $invitation->content ?? [];

        // ===== HERO =====
        $content['hero']['title'] = $request->input('hero.title', $content['hero']['title'] ?? null);
        $content['hero']['date_formatted'] = $request->input('hero.date_formatted', $content['hero']['date_formatted'] ?? null);

        if ($request->hasFile('hero_background')) {
            $path = $request->file('hero_background')->store('invitation', 'public');
            $content['hero']['background_url'] = Storage::url($path);
        }

        if ($request->hasFile('hero_music')) {
            $path = $request->file('hero_music')->store('invitation/music', 'public');
            $content['hero']['music_url'] = Storage::url($path);
        }

        // ===== MEMPELAI =====
        foreach (['pria', 'wanita'] as $key) {
            $content['mempelai'][$key]['nama_lengkap'] = $request->input("mempelai.$key.nama_lengkap");
            $content['mempelai'][$key]['nama_panggilan'] = $request->input("mempelai.$key.nama_panggilan");
            $content['mempelai'][$key]['ayah'] = $request->input("mempelai.$key.ayah");
            $content['mempelai'][$key]['ibu'] = $request->input("mempelai.$key.ibu");

            if ($request->hasFile("mempelai_{$key}_foto")) {
                $path = $request->file("mempelai_{$key}_foto")->store('invitation', 'public');
                $content['mempelai'][$key]['foto_url'] = Storage::url($path);
            }
        }

        // ===== ACARA (repeatable array, dikirim sebagai JSON string) =====
        if ($request->filled('acara')) {
            $content['acara'] = json_decode($request->input('acara'), true) ?? [];
        }

        // ===== STORIES (repeatable array) =====
        // if ($request->filled('stories')) {
        //     $content['stories'] = json_decode($request->input('stories'), true) ?? [];
        // }

        // ===== VERSES (repeatable array) =====
        if ($request->filled('verses')) {
            $content['verses'] = json_decode($request->input('verses'), true) ?? [];
        }

        // ===== MOODBOARD: COLORS (repeatable array) =====
        if ($request->filled('moodboard_colors')) {
            $content['moodboard']['colors'] = json_decode($request->input('moodboard_colors'), true) ?? [];
        }

// ===== MOODBOARD: ANNOUNCEMENTS (repeatable array) =====
if ($request->filled('moodboard_announcements')) {
    $content['moodboard']['announcements'] = json_decode($request->input('moodboard_announcements'), true) ?? [];
}

        $invitation->update(['content' => $content]);

        return back()->with('success', 'Invitation berhasil diupdate!');
    }
}