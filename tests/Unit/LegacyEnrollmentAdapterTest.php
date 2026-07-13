<?php

use App\Domain\Booking\Adapters\LegacyEnrollmentAdapter;
use App\Domain\Booking\DataTransferObjects\CreateBookingData;

// Same two CREATE TABLE statements as legacy/schema.sql (Chapter 11): kept
// in sync by hand, not read from that file, so this suite never depends on a path
// outside this repository.
function legacyConnection(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec(<<<'SQL'
        CREATE TABLE courses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            starts_at TEXT NOT NULL,
            total_seats INTEGER NOT NULL,
            occupied_seats INTEGER NOT NULL DEFAULT 0
        );
        SQL);
    $pdo->exec(<<<'SQL'
        CREATE TABLE enrollments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id INTEGER NOT NULL,
            participant_name TEXT NOT NULL,
            participant_email TEXT NOT NULL,
            created_at TEXT NOT NULL
        );
        SQL);

    return $pdo;
}

test('translates a legacy enrollment into a CreateBookingData DTO', function () {
    $pdo = legacyConnection();
    $pdo->exec("INSERT INTO courses (id, title, starts_at, total_seats) VALUES (1, 'Introduzione a PHP', '2026-09-01 09:00:00', 20)");
    $pdo->exec("INSERT INTO enrollments (id, course_id, participant_name, participant_email, created_at) VALUES (1, 1, 'Mario Rossi', 'mario@example.com', '2026-07-01 10:00:00')");

    $data = (new LegacyEnrollmentAdapter($pdo))->translate(1);

    expect($data)->toBeInstanceOf(CreateBookingData::class)
        ->and($data->participantName)->toBe('Mario Rossi')
        ->and($data->participantEmail)->toBe('mario@example.com')
        ->and($data->seats)->toBe(1)
        ->and($data->participantId)->toBeNull();
});

test('throws explicitly when the legacy enrollment does not exist', function () {
    $pdo = legacyConnection();

    (new LegacyEnrollmentAdapter($pdo))->translate(999);
})->throws(RuntimeException::class, 'Legacy enrollment 999 not found.');
