<?php

use App\Exceptions\ApiVersionRemovedException;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LegacyEnrollmentController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\V2\BookingController as V2BookingController;
use Illuminate\Support\Facades\Route;

// Authentication is not tied to a domain version: registering or logging in does not carry
// a data contract that Chapter 3's versioning strategy needs to protect. It stays outside the
// v1/v2 prefixes, alongside the routes it then guards.
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Same reasoning as above, for the other population of callers: a partner does not register
// or log in, it presents the client_id/client_secret it received once, out of band, against
// Passport's own /oauth/token endpoint, and attaches the token it gets back to this route.
Route::get('partner/ping', [PartnerController::class, 'ping'])->middleware('client');

// Stands in for the legacy gestionale's own enrollment form (Chapter 11): a guest booking
// arriving from a system with no accounts of its own, not a versioned part of this API's own
// evolution, so it stays outside v1/v2 too, deliberately without auth:sanctum. Still throttled,
// by IP rather than by user, exactly as Chapter 4 already does for other unauthenticated routes.
Route::post('legacy-enrollments', [LegacyEnrollmentController::class, 'store'])
    ->middleware('throttle:legacy-enrollments');

Route::prefix('v1')->name('v1.')->group(function () {
    Route::apiResource('events', EventController::class);
    Route::post('events/{event}/cover-image', [EventController::class, 'uploadCoverImage']);
    Route::apiResource('events.bookings', BookingController::class)->shallow()->except('destroy');

    // The unrestricted cancellation behavior replaced by v2's policy: still fully
    // functional for consumers who have not migrated yet, but flagged as deprecated so they
    // know it will not stay available indefinitely.
    Route::delete('bookings/{booking}', [BookingController::class, 'destroy'])
        ->name('bookings.destroy')
        ->middleware('deprecated:2026-10-04');
});

Route::prefix('v2')->name('v2.')->group(function () {
    Route::apiResource('events', EventController::class);
    Route::post('events/{event}/cover-image', [EventController::class, 'uploadCoverImage']);
    Route::apiResource('events.bookings', BookingController::class)->shallow()->except('destroy');
    Route::delete('bookings/{booking}', [V2BookingController::class, 'destroy'])->name('bookings.destroy');
});

// Versions predating this book's timeline (e.g. v0) have already been removed. Any request
// against one of them fails fast with a dedicated, stable error instead of a generic 404,
// so a consumer stuck on a removed version gets an unambiguous signal, not a guess.
Route::any('{version}/{any?}', function (string $version) {
    throw new ApiVersionRemovedException($version);
})->whereIn('version', ['v0'])->where('any', '.*');
