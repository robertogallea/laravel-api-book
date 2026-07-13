<?php

namespace App\Domain\Booking\Notifiers;

use App\Domain\Booking\Contracts\BookingNotifier;
use App\Domain\Booking\Jobs\SendBookingCancelledWebhook;
use App\Domain\Booking\Jobs\SendBookingConfirmedWebhook;
use App\Models\Booking;

class WebhookBookingNotifier implements BookingNotifier
{
    public function notifyCreated(Booking $booking): void
    {
        SendBookingConfirmedWebhook::dispatch($booking);
    }

    public function notifyCancelled(Booking $booking): void
    {
        SendBookingCancelledWebhook::dispatch($booking->id, $booking->event_id);
    }
}
