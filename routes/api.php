<?php

use App\Http\Controllers\GuestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
| Public guest endpoints, keyed only by guest_code (AGENTS.md 5.2).
| The tenant is resolved from the guest, then the same lifecycle checks as the
| public page apply (expired -> 403, archived/unknown -> 404).
*/
Route::middleware('tenant.guestcode')->group(function () {
    Route::get('/guests/{code}', [GuestController::class, 'show']);
    Route::post('/rsvp/{code}', [GuestController::class, 'updateRsvp']);
});
