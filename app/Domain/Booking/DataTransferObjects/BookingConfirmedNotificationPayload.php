<?php

namespace App\Domain\Booking\DataTransferObjects;

use App\Models\Booking;

final class BookingConfirmedNotificationPayload
{
    public function __construct(
        public readonly int $bookingId,
        public readonly int $eventId,
        public readonly string $eventTitle,
        public readonly string $participantName,
        public readonly string $participantEmail,
        public readonly int $seats,
        public readonly string $confirmedAt,
    ) {}

    public static function fromBooking(Booking $booking): self
    {
        // loadMissing(), not a bare $booking->event access: neither CreateBookingAction nor
        // SerializesModels (on the queued job/notification that call this) guarantees this
        // relation is already loaded, and Model::preventLazyLoading() does not guard a single
        // model's relations, only collections of two or more rows, so an unguarded access here
        // would silently issue an extra query instead of failing loudly. Made explicit instead.
        $booking->loadMissing('event');

        return new self(
            bookingId: $booking->id,
            eventId: $booking->event_id,
            eventTitle: $booking->event->title,
            participantName: $booking->participant_name,
            participantEmail: $booking->participant_email,
            seats: $booking->seats,
            confirmedAt: $booking->created_at->toIso8601String(),
        );
    }

    public function toArray(): array
    {
        return [
            'event' => 'booking.confirmed',
            'data' => [
                'booking_id' => $this->bookingId,
                'event_id' => $this->eventId,
                'event_title' => $this->eventTitle,
                'participant_name' => $this->participantName,
                'participant_email' => $this->participantEmail,
                'seats' => $this->seats,
                'confirmed_at' => $this->confirmedAt,
            ],
        ];
    }
}
