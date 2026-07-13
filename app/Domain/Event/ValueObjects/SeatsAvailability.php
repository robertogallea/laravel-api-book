<?php

namespace App\Domain\Event\ValueObjects;

use App\Models\Event;

final class SeatsAvailability
{
    private function __construct(
        public readonly int $capacity,
        public readonly int $booked,
    ) {}

    public static function forEvent(Event $event): self
    {
        return new self(
            capacity: $event->capacity,
            // bookings()->sum(), not bookings->sum(): a single SUM(seats) computed by the
            // database, not every booking row pulled into PHP just to add them up here. Callers
            // that already need the full collection for something else are unaffected either way.
            booked: (int) $event->bookings()->sum('seats'),
        );
    }

    public function remaining(): int
    {
        return $this->capacity - $this->booked;
    }

    public function canAccommodate(int $seats): bool
    {
        return $seats <= $this->remaining();
    }
}
