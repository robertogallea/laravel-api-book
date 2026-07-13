<?php

namespace App\Http\Controllers\V2;

use App\Domain\Booking\Actions\CancelBookingAction;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BookingController extends Controller implements HasMiddleware
{
    // Every other booking endpoint (index, store, show, update) is unchanged between v1 and v2,
    // and stays served by the shared App\Http\Controllers\BookingController. Only cancellation,
    // the one behavior the new policy touches, gets its own version-specific controller.

    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
            // Same ownership check as v1's destroy: only the booking's participant, or an
            // admin, may cancel it. BookingPolicy is shared across both versions.
            new Middleware('can:delete,booking'),
        ];
    }

    public function destroy(Booking $booking, CancelBookingAction $action)
    {
        $action($booking);

        return response()->json(status: 204);
    }
}
