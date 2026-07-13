<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::apiResource('events', EventController::class);
Route::apiResource('events.bookings', BookingController::class)->shallow();
