<?php

namespace App\Domain\Booking\DataTransferObjects;

final class CreateBookingData
{
    public function __construct(
        public readonly string $participantName,
        public readonly string $participantEmail,
        public readonly int $seats,
        // Null for a booking with no authenticated owner, such as one created from the command
        // line (CreateBookingCommand): a missing owner is a legitimate state, not an error, and
        // BookingPolicy already treats an ownerless booking as manageable only by an admin.
        public readonly ?int $participantId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            participantName: $data['participant_name'],
            participantEmail: $data['participant_email'],
            seats: $data['seats'],
            participantId: $data['participant_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'participant_name' => $this->participantName,
            'participant_email' => $this->participantEmail,
            'seats' => $this->seats,
            'participant_id' => $this->participantId,
        ];
    }
}
