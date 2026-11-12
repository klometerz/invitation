<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Content editor for the CURRENT tenant's invitation.
 *
 * Fixes K1: no Invitation::first(), and it never auto-creates a row.
 */
class InvitationContentController extends Controller
{
    public function edit()
    {
        $invitation = Invitation::findOrFail(app(TenantContext::class)->require());

        return Inertia::render('Admin/InvitationEdit', [
            'invitation' => $invitation,
        ]);
    }

    public function update(Request $request)
    {
        $invitation = Invitation::findOrFail(app(TenantContext::class)->require());

        $content = is_array($invitation->content) ? $invitation->content : [];

        // Null-safe structure: legacy rows may be missing whole branches.
        $content['hero'] = is_array($content['hero'] ?? null) ? $content['hero'] : [];
        $content['mempelai'] = is_array($content['mempelai'] ?? null) ? $content['mempelai'] : [];
        foreach (['pria', 'wanita'] as $partner) {
            $content['mempelai'][$partner] = is_array($content['mempelai'][$partner] ?? null)
                ? $content['mempelai'][$partner]
                : [];
        }
        $content['moodboard'] = is_array($content['moodboard'] ?? null) ? $content['moodboard'] : [];

        $invitationId = $invitation->id;

        // ===== HERO =====
        $content['hero']['title'] = $request->input('hero.title', $content['hero']['title'] ?? null);
        $content['hero']['date_formatted'] = $request->input('hero.date_formatted', $content['hero']['date_formatted'] ?? null);

        if ($request->hasFile('hero_background')) {
            $path = $request->file('hero_background')->store("invitations/{$invitationId}/hero", 'public');
            $content['hero']['background_url'] = Storage::url($path);
        }

        if ($request->hasFile('hero_music')) {
            $path = $request->file('hero_music')->store("invitations/{$invitationId}/music", 'public');
            $content['hero']['music_url'] = Storage::url($path);
        }

        // ===== MEMPELAI =====
        foreach (['pria', 'wanita'] as $key) {
            $content['mempelai'][$key]['nama_lengkap'] = $request->input("mempelai.$key.nama_lengkap");
            $content['mempelai'][$key]['nama_panggilan'] = $request->input("mempelai.$key.nama_panggilan");
            $content['mempelai'][$key]['ayah'] = $request->input("mempelai.$key.ayah");
            $content['mempelai'][$key]['ibu'] = $request->input("mempelai.$key.ibu");

            if ($request->hasFile("mempelai_{$key}_foto")) {
                $path = $request->file("mempelai_{$key}_foto")->store("invitations/{$invitationId}/mempelai", 'public');
                $content['mempelai'][$key]['foto_url'] = Storage::url($path);
            }
        }

        // ===== ACARA (repeatable array, dikirim sebagai JSON string) =====
        if ($request->filled('acara')) {
            $content['acara'] = json_decode($request->input('acara'), true) ?? [];
        }

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
