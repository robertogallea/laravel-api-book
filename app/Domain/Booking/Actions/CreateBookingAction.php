<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Contracts\BookingNotifier;
use App\Domain\Booking\DataTransferObjects\CreateBookingData;
use App\Domain\Event\ValueObjects\SeatsAvailability;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBookingAction
{
    public function __construct(
        private readonly BookingNotifier $notifier,
    ) {}

    public function __invoke(Event $event, CreateBookingData $data): Booking
    {
        $booking = DB::transaction(function () use ($event, $data) {
            // Re-fetched, not the $event handed in by route model binding: locking that
            // instance would lock nothing, because it was already read (and its row lock,
            // if any, released) before this transaction even began. Only a row read for the
            // first time inside this transaction can hold the lock until commit.
            $lockedEvent = Event::without('bookings')->lockForUpdate()->findOrFail($event->id);

            $availability = SeatsAvailability::forEvent($lockedEvent);

            if (! $availability->canAccommodate($data->seats)) {
                throw ValidationException::withMessages([
                    'seats' => ["Only {$availability->remaining()} seat(s) available for this event."],
                ]);
            }

            $booking = $lockedEvent->bookings()->create($data->toArray());

            if ($availability->booked + $data->seats === $availability->capacity) {
                $lockedEvent->update(['sold_out_at' => now()]);
            }

            return $booking;
        });

        $this->notifier->notifyCreated($booking);

        return $booking;
    }
}
