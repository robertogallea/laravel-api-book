<?php

namespace App\Domain\Booking\Adapters;

use App\Domain\Booking\DataTransferObjects\CreateBookingData;
use PDO;
use RuntimeException;

// Anti-corruption layer: the only place in EventHub that knows the shape of the
// legacy gestionale's `enrollments` table. Nothing downstream of translate()
// ever sees a legacy row, only the DTO EventHub already speaks.
final class LegacyEnrollmentAdapter
{
    public function __construct(private readonly PDO $legacyConnection) {}

    public function translate(int $enrollmentId): CreateBookingData
    {
        $statement = $this->legacyConnection->prepare(
            'SELECT * FROM enrollments WHERE id = ?'
        );
        $statement->execute([$enrollmentId]);
        $enrollment = $statement->fetch(PDO::FETCH_ASSOC);

        if ($enrollment === false) {
            throw new RuntimeException("Legacy enrollment {$enrollmentId} not found.");
        }

        return CreateBookingData::fromArray([
            'participant_name' => $enrollment['participant_name'],
            'participant_email' => $enrollment['participant_email'],
            // The legacy gestionale has no notion of a multi-seat enrollment:
            // every row is exactly one participant, one seat.
            'seats' => 1,
            // The legacy gestionale has no user accounts to map to: EventHub
            // already treats a booking with no owner as a legitimate guest
            // booking (see CreateBookingData), not a special case invented
            // for this migration.
            'participant_id' => null,
        ]);
    }
}
