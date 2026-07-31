<?php

use App\Http\Controllers\GuestController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\WeddingController;
use App\Models\Invitation;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Wedding');
});
Route::get('/api/guests/{code}', [GuestController::class, 'show']);
Route::post('/rsvp/{code}', [GuestController::class, 'updateRsvp']);

// Grouping biar rapi, nanti bisa lo kasih middleware 'auth' kalau mau diproteksi
Route::prefix('admin')->group(function () {
    // Halaman List Tamu
    Route::get('/guests', [GuestController::class, 'index'])->name('admin.guests.index');
    
    // Action Import Excel
    Route::post('/guests/import', [GuestController::class, 'import'])->name('admin.guests.import');
    Route::delete('/guests/{id}', [GuestController::class, 'destroy'])->name('admin.guests.destroy');
    Route::delete('/guests-truncate', [GuestController::class, 'truncate'])->name('admin.guests.truncate');

    Route::get('/gallery', [GalleryController::class, 'index'])->name('admin.gallery.index');
    Route::post('/gallery', [GalleryController::class, 'store'])->name('admin.gallery.store');
    Route::delete('/gallery/{id}', [GalleryController::class, 'destroy'])->name('admin.gallery.destroy');
   

Route::get('/guests/print-labels', [GuestController::class, 'printLabels'])->name('admin.guests.print');
});

// routes/web.php
Route::get('/v/{slug}', function ($slug) {
    $invitation = Invitation::where('slug', $slug)->with('theme')->firstOrFail();
    
    // Kirim data konten dan path tema ke Inertia
    return Inertia::render($invitation->theme->svelte_path, [
        'content' => $invitation->content,
        'guests' => request('to') // Misal: /v/maulia-kahfi?to=Budi
    ]);
})->name('wedding.index');