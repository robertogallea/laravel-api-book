<?php

namespace App\Http\Controllers\V2;

use App\Domain\Booking\Actions\CancelBookingAction;
use App\Http\Controllers\Controller;
use App\Models\Booking;

class BookingController extends Controller
{
    // Every other booking endpoint (index, store, show, update) is unchanged between v1 and v2,
    // and stays served by the shared App\Http\Controllers\BookingController. Only cancellation,
    // the one behavior the new policy touches, gets its own version-specific controller.

    public function destroy(Booking $booking, CancelBookingAction $action)
    {
        $action($booking);

        return response()->json(status: 204);
    }
}
