<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->role === Role::Admin
            || ($booking->participant_id !== null && $booking->participant_id === $user->id);
    }

    public function update(User $user, Booking $booking): bool
    {
        return $this->view($user, $booking);
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $this->view($user, $booking);
    }
}
