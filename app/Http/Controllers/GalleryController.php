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
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', // Max 2MB per foto
        ]);

        if ($request->hasFile('images')) {
            $invitationId = app(TenantContext::class)->require();

            foreach ($request->file('images') as $image) {
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
