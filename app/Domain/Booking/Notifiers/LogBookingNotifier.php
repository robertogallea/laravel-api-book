<?php

namespace App\Domain\Booking\Notifiers;

use App\Domain\Booking\Contracts\BookingNotifier;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;

class LogBookingNotifier implements BookingNotifier
{
    public function notifyCreated(Booking $booking): void
    {
        Log::info("Booking #{$booking->id} created for event #{$booking->event_id}.");
    }

    public function notifyCancelled(Booking $booking): void
    {
        Log::info("Booking #{$booking->id} cancelled for event #{$booking->event_id}.");
    }
}
