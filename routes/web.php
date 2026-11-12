<?php

use App\Http\Controllers\GuestController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\WeddingController;
use App\Models\Invitation;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Gallery;
use App\Models\Guest;
use App\Http\Controllers\Admin\ScannerController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;  
use App\Http\Controllers\PhotoboothController; 

// Route::get('/', function () {
//     $guestCode = request('guest');
//     $guestData = $guestCode ? Guest::where('guest_code', $guestCode)->first() : null;
//     $invitation = Invitation::first();
//     $coverGallery = Gallery::orderBy('order')->first();

//     \Illuminate\Support\Facades\View::share('seo', [
//         'title' => $invitation?->title ?? 'Undangan Pernikahan',
//         'description' => 'Anda diundang untuk hadir di acara pernikahan kami.',
//         'image' => $coverGallery ? url($coverGallery->path) : url('/mk.png'), // ← FIX: hapus 'storage/' yang dobel
//         'url' => url()->current(),
//     ]);

//     return Inertia::render('Wedding', [
//         'invitation' => $invitation,
//         'content' => $invitation?->content,
//         'guestData' => $guestData,
//         'galleries' => Gallery::orderBy('order')->get(),
//     ]);
// });


use SimpleSoftwareIO\QrCode\Facades\QrCode;

Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);

    if (!file_exists($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath);
})->where('path', '.*');

Route::get('/', function () {
    $guestCode = request('guest');
    $guestData = $guestCode ? Guest::where('guest_code', $guestCode)->first() : null;
    $invitation = Invitation::first();
    $coverGallery = Gallery::orderBy('order')->first();

    // Attach QR code kalau ada guest yang buka link ini
    if ($guestData) {
        $qrUrl = url('/?guest=' . $guestData->guest_code);
        $guestData->qrcode = QrCode::size(200)->margin(1)->generate($qrUrl)->toHtml();
    }

    \Illuminate\Support\Facades\View::share('seo', [
        'title' => $invitation?->title ?? 'Undangan Pernikahan',
        'description' => 'Anda diundang untuk hadir di acara pernikahan kami.',
        'image' => $coverGallery ? url($coverGallery->path) : url('/mk.png'),
        'url' => url()->current(),
    ]);

    return Inertia::render('Wedding', [
        'invitation' => $invitation,
        'content' => $invitation?->content,
        'guestData' => $guestData,
        'galleries' => Gallery::orderBy('order')->get(),
    ]);
});


Route::get('/photobooth', [PhotoboothController::class, 'index']);
Route::post('/photobooth/capture', [PhotoboothController::class, 'store']);
Route::get('/api/guests/{code}', [GuestController::class, 'show']);
// Route::post('/rsvp/{code}', [GuestController::class, 'updateRsvp']);

// ===== AUTH ROUTES (public, buat login) =====
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// Grouping biar rapi, nanti bisa lo kasih middleware 'auth' kalau mau diproteksi
Route::prefix('admin')->middleware('auth')->group(function () {
    // Halaman List Tamu
    Route::get('/guests', [GuestController::class, 'index'])->name('admin.guests.index');
    
    // Action Import Excel
    Route::post('/guests/import', [GuestController::class, 'import'])->name('admin.guests.import');
    Route::delete('/guests/{id}', [GuestController::class, 'destroy'])->name('admin.guests.destroy');
    Route::delete('/guests-truncate', [GuestController::class, 'truncate'])->name('admin.guests.truncate');
     Route::post('/guests', [GuestController::class, 'store'])->name('admin.guests.store');
    Route::put('/guests/{id}', [GuestController::class, 'update'])->name('admin.guests.update');
    Route::get('/gallery', [GalleryController::class, 'index'])->name('admin.gallery.index');
    Route::post('/gallery', [GalleryController::class, 'store'])->name('admin.gallery.store');
    Route::delete('/gallery/{id}', [GalleryController::class, 'destroy'])->name('admin.gallery.destroy');
    
     Route::get('/scanner', [ScannerController::class, 'index'])->name('admin.scanner.index');
    Route::post('/scanner/checkin', [ScannerController::class, 'checkin'])->name('admin.scanner.checkin');
         Route::get('/invitation', [InvitationController::class, 'edit'])->name('admin.invitation.edit');
    Route::post('/invitation', [InvitationController::class, 'update'])->name('admin.invitation.update');
    
    Route::get('/guests/export', [GuestController::class, 'export'])->name('admin.guests.export');
Route::get('/guests/print-labels', [GuestController::class, 'printLabels'])->name('admin.guests.print');
Route::get('/scanner/stats', [ScannerController::class, 'todayStats'])->name('admin.scanner.stats');
Route::get('/photobooth', [PhotoboothController::class, 'adminIndex'])->name('admin.photobooth.index');
    Route::delete('/photobooth/{id}', [PhotoboothController::class, 'destroy'])->name('admin.photobooth.destroy');
    Route::get('/photobooth/print', [PhotoboothController::class, 'printView'])->name('admin.photobooth.print');

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