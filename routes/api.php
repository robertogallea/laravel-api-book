<?php

use App\Exceptions\ApiVersionRemovedException;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\V2\BookingController as V2BookingController;
use Illuminate\Support\Facades\Route;

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
