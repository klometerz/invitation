<?php

use App\Http\Controllers\Admin\InvitationContentController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\ScannerController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PhotoboothController;
use App\Http\Controllers\WeddingController;
use App\Support\SlugRules;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public infrastructure
|--------------------------------------------------------------------------
*/

// Existing uploads are served through this route, not only through the
// storage symlink. Keep it available while legacy files sit in storage/app/public.
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/'.$path);

    if (! file_exists($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath);
})->where('path', '.*');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Landing + legacy links (printed QR codes / WhatsApp already in the wild)
|--------------------------------------------------------------------------
*/

// Phase 1 placeholder: redirects to the showcase invitation. P3-T6 replaces
// this with the marketing landing page.
Route::get('/', HomeController::class)->name('home');

// Legacy `/?guest=CODE` is handled by HomeController (it redirects to the slug).

// Legacy `/photobooth?guest=CODE` -> `/{slug}/photobooth?guest=CODE`
Route::get('/photobooth', [PhotoboothController::class, 'legacyRedirect'])
    ->name('legacy.photobooth');

// Legacy capture endpoint still used by the current photobooth frontend.
Route::post('/photobooth/capture', [PhotoboothController::class, 'store'])
    ->middleware(['tenant.guestcode', 'feature:photobooth'])
    ->name('legacy.photobooth.capture');

// Decision C2: `/v/{slug}` was the original public URL. It becomes a permanent
// redirect to the canonical `/{slug}` so shared links keep working.
Route::get('/v/{slug}', function (string $slug) {
    return redirect('/'.$slug, 301);
})->where('slug', SlugRules::PATTERN)->name('legacy.invitation');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->middleware('auth')->group(function () {
    /*
     | Routes that do NOT operate on a single tenant (the owner manages the
     | list of weddings and their staff accounts here).
     */
    Route::middleware('role:admin')->group(function () {
        Route::get('/invitations', [InvitationController::class, 'index'])->name('admin.invitations.index');
        Route::get('/invitations/create', [InvitationController::class, 'create'])->name('admin.invitations.create');
        Route::post('/invitations', [InvitationController::class, 'store'])->name('admin.invitations.store');
        Route::get('/invitations/{id}/edit', [InvitationController::class, 'edit'])->name('admin.invitations.edit');
        Route::put('/invitations/{id}', [InvitationController::class, 'update'])->name('admin.invitations.update');
        Route::post('/invitations/{id}/duplicate', [InvitationController::class, 'duplicate'])->name('admin.invitations.duplicate');
        Route::post('/invitations/{id}/archive', [InvitationController::class, 'archive'])->name('admin.invitations.archive');

        Route::post('/tenant/switch', [InvitationController::class, 'switch'])->name('admin.tenant.switch');

        Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('admin.users.update');
        Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset-password');
    });

    /*
     | Routes that need a selected tenant.
     */
    Route::middleware(['tenant.admin', 'tenant.selected'])->group(function () {
        // Staff and admin: check-in scanner only.
        Route::middleware(['role:admin,staff', 'feature:guestbook'])->group(function () {
            Route::get('/scanner', [ScannerController::class, 'index'])->name('admin.scanner.index');
            Route::post('/scanner/checkin', [ScannerController::class, 'checkin'])->name('admin.scanner.checkin');
            Route::get('/scanner/stats', [ScannerController::class, 'todayStats'])->name('admin.scanner.stats');
        });

        // Admin only: everything else for the selected wedding.
        Route::middleware('role:admin')->group(function () {
            // Guests
            Route::get('/guests', [GuestController::class, 'index'])->name('admin.guests.index');
            Route::post('/guests', [GuestController::class, 'store'])->name('admin.guests.store');
            Route::post('/guests/import', [GuestController::class, 'import'])->name('admin.guests.import');
            Route::get('/guests/export', [GuestController::class, 'export'])
                ->middleware('feature:guestbook')->name('admin.guests.export');
            Route::get('/guests/print-labels', [GuestController::class, 'printLabels'])
                ->middleware('feature:qr_cards')->name('admin.guests.print');
            Route::delete('/guests/reset', [GuestController::class, 'resetGuests'])->name('admin.guests.reset');
            Route::put('/guests/{id}', [GuestController::class, 'update'])->name('admin.guests.update');
            Route::delete('/guests/{id}', [GuestController::class, 'destroy'])->name('admin.guests.destroy');

            // Gallery
            Route::middleware('feature:gallery')->group(function () {
                Route::get('/gallery', [GalleryController::class, 'index'])->name('admin.gallery.index');
                Route::post('/gallery', [GalleryController::class, 'store'])->name('admin.gallery.store');
                Route::delete('/gallery/{id}', [GalleryController::class, 'destroy'])->name('admin.gallery.destroy');
            });

            // Invitation content editor
            Route::get('/invitation', [InvitationContentController::class, 'edit'])->name('admin.invitation.edit');
            Route::post('/invitation', [InvitationContentController::class, 'update'])->name('admin.invitation.update');

            // Photobooth (admin grid + printable memory book)
            Route::middleware('feature:photobooth')->group(function () {
                Route::get('/photobooth', [PhotoboothController::class, 'adminIndex'])->name('admin.photobooth.index');
                Route::delete('/photobooth/{id}', [PhotoboothController::class, 'destroy'])->name('admin.photobooth.destroy');
            });

            Route::get('/photobooth/print', [PhotoboothController::class, 'printView'])
                ->middleware('feature:memory_book')->name('admin.photobooth.print');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Public invitation (declared LAST, constrained so literal routes win)
|--------------------------------------------------------------------------
*/

Route::get('/{slug}/photobooth', [PhotoboothController::class, 'index'])
    ->where('slug', SlugRules::PATTERN)
    ->middleware(['tenant.public', 'feature:photobooth'])
    ->name('invitation.photobooth');

Route::post('/{slug}/photobooth/capture', [PhotoboothController::class, 'store'])
    ->where('slug', SlugRules::PATTERN)
    ->middleware(['tenant.public', 'feature:photobooth'])
    ->name('invitation.photobooth.capture');

Route::get('/{slug}', [WeddingController::class, 'index'])
    ->where('slug', SlugRules::PATTERN)
    ->middleware('tenant.public')
    ->name('invitation.show');
