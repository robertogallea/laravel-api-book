<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Contracts\BookingNotifier;
use App\Domain\Event\ValueObjects\SeatsAvailability;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelBookingAction
{
    public function __construct(
        private readonly BookingNotifier $notifier,
    ) {}

    public function __invoke(Booking $booking): void
    {
        // without('bookings'), and queried once here instead of through $booking->event:
        // this event is only needed for its own columns (starts_at, sold_out_at), not its
        // booking collection, and a single fetch is reused below instead of a second,
        // unrelated one for the same row.
        $event = Event::without('bookings')->findOrFail($booking->event_id);

        if (now()->greaterThan($event->starts_at->subHours(24))) {
            throw ValidationException::withMessages([
                'booking' => ['Bookings can no longer be cancelled less than 24 hours before the event starts.'],
            ]);
        }

        DB::transaction(function () use ($booking, $event) {
            // Re-fetched under lock, same reason as CreateBookingAction: the copy read above,
            // before this transaction opened, holds no lock and could already be stale by the
            // time this cancellation actually commits.
            $lockedEvent = Event::without('bookings')->lockForUpdate()->findOrFail($event->id);

            $booking->delete();

            // sold_out_at was, until this fix, only ever set (CreateBookingAction), never
            // cleared: cancelling the last booking of a sold-out event left it permanently
            // excluded from Event::scopeAvailable() (Capitolo 8) even after a seat freed up.
            // SeatsAvailability is recomputed after the delete above, inside the same lock
            // CreateBookingAction relies on, so this can't race a concurrent booking deciding
            // sold_out_at from a stale count.
            if ($lockedEvent->sold_out_at !== null && SeatsAvailability::forEvent($lockedEvent)->remaining() > 0) {
                $lockedEvent->update(['sold_out_at' => null]);
            }
        });

        $this->notifier->notifyCancelled($booking);
    }
}
