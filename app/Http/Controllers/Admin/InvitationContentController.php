<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Support\InvitationPresenter;
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

        // P2-T9: explicit mime + size limits with Indonesian messages.
        $request->validate([
            'hero_background' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:8192'],
            'hero_music' => ['nullable', 'file', 'mimes:mp3,wav,m4a,ogg', 'max:20480'],
            'mempelai_pria_foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'mempelai_wanita_foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'qris_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'hero_background.image' => 'Background harus berupa gambar.',
            'hero_background.mimes' => 'Background harus JPG, PNG, atau WEBP.',
            'hero_background.max' => 'Background maksimal 8 MB.',
            'hero_music.mimes' => 'Musik harus MP3, WAV, M4A, atau OGG.',
            'hero_music.max' => 'Musik maksimal 20 MB.',
            'mempelai_pria_foto.max' => 'Foto mempelai maksimal 4 MB.',
            'mempelai_wanita_foto.max' => 'Foto mempelai maksimal 4 MB.',
            'logo.max' => 'Logo maksimal 2 MB.',
            'qris_image.max' => 'Gambar QRIS maksimal 2 MB.',
        ]);

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
        $content['hero']['hashtag'] = $request->input('hero.hashtag', $content['hero']['hashtag'] ?? null);

        if ($request->hasFile('hero_background')) {
            $path = $request->file('hero_background')->store("invitations/{$invitationId}/hero", 'public');
            $content['hero']['background_url'] = Storage::url($path);
        }

        if ($request->hasFile('hero_music')) {
            $path = $request->file('hero_music')->store("invitations/{$invitationId}/music", 'public');
            $content['hero']['music_url'] = Storage::url($path);
        }

        // ===== BRANDING (P2-T2: per-invitation logo, no /mk.png default) =====
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store("invitations/{$invitationId}/logo", 'public');
            $content['logo_url'] = Storage::url($path);
        }

        // ===== LOADING TIPS (one per line) =====
        if ($request->has('loading_tips')) {
            $raw = (string) $request->input('loading_tips', '');
            $tips = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: [])));

            $content['loading_tips'] = $tips;
        }

        // ===== INVITE MESSAGE TEMPLATE (P2-T8) =====
        if ($request->has('invite_message_template')) {
            $style = trim((string) $request->input('invite_message_template', ''));
            $content['invite_message_template'] = $style !== ''
                ? $style
                : InvitationPresenter::defaultInviteMessageTemplate();
        }

        // ===== GIFT / THANKS (P2-T2) =====
        if ($request->has('thanks_accounts')) {
            $accounts = json_decode((string) $request->input('thanks_accounts'), true);

            $content['thanks']['bank_accounts'] = is_array($accounts)
                ? array_values(array_filter($accounts, function ($account) {
                    return is_array($account)
                        && (trim((string) ($account['bank'] ?? '')) !== ''
                            || trim((string) ($account['number'] ?? '')) !== '');
                }))
                : [];
        }

        if ($request->hasFile('qris_image')) {
            $path = $request->file('qris_image')->store("invitations/{$invitationId}/gift", 'public');
            $content['thanks']['qris_image_url'] = Storage::url($path);
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
