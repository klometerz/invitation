<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GalleryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/GalleryIndex', [
            // Scoped automatically by TenantScope.
            'images' => Gallery::orderBy('order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'images' => ['required', 'array'],
            'images.*' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'images.required' => 'Pilih minimal satu foto.',
            'images.*.image' => 'Semua berkas harus berupa gambar.',
            'images.*.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'images.*.max' => 'Setiap foto maksimal 5 MB.',
        ]);

        if ($request->hasFile('images')) {
            $invitationId = app(TenantContext::class)->require();
            $invitation = \App\Models\Invitation::findOrFail($invitationId);

            // P3-T3: enforce max_gallery_images. Checked BEFORE writing, so a
            // rejected batch never leaves a partial upload behind.
            $limit = $invitation->limit('max_gallery_images');
            $files = $request->file('images');

            if ($limit !== null) {
                $existing = Gallery::count();

                if ($existing + count($files) > $limit) {
                    return back()->withErrors([
                        'images' => "Batas paket adalah {$limit} foto galeri. Saat ini "
                            ."{$existing} foto, Anda memilih ".count($files).'.',
                    ]);
                }
            }

            foreach ($files as $image) {
                // Per-tenant directory (AGENTS.md 5.9).
                $path = $image->store("invitations/{$invitationId}/gallery", 'public');

                Gallery::create([
                    'invitation_id' => $invitationId,
                    'filename' => $image->getClientOriginalName(),
                    'path' => Storage::url($path),
                ]);
            }
        }

        return back()->with('success', 'Foto berhasil di-upload, Bre!');
    }

    public function destroy($id)
    {
        // Scoped: another tenant's id 404s.
        $image = Gallery::findOrFail($id);

        // Hapus file fisik dari storage
        $relativePath = str_replace('/storage/', '', $image->path);
        Storage::disk('public')->delete($relativePath);

        // Hapus data dari database
        $image->delete();

        return back()->with('success', 'Foto dihapus!');
    }
}
