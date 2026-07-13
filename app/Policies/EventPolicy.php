<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function create(User $user): bool
    {
        return $user->role === Role::Organizer || $user->role === Role::Admin;
    }

    public function update(User $user, Event $event): bool
    {
        return $user->role === Role::Admin
            || ($user->role === Role::Organizer && $event->organizer_id === $user->id);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    /**
     * Whether the user may list the bookings made for this event: a concern of the event's
     * owner, not of any single booking's owner, so it lives on EventPolicy, not BookingPolicy.
     */
    public function viewBookings(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }
}
