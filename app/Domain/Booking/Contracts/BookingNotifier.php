<?php

namespace App\Domain\Booking\Contracts;

use App\Models\Booking;

interface BookingNotifier
{
    public function notifyCreated(Booking $booking): void;

    public function notifyCancelled(Booking $booking): void;
}
