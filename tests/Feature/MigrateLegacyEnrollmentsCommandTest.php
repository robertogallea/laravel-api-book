<?php

use App\Models\Event;

// Same two CREATE TABLE statements as legacy/schema.sql (Chapter 11), kept in
// sync by hand: this suite never depends on a path outside this repository, a real
// temporary SQLite file stands in for the legacy database instead.
function createTemporaryLegacyDatabase(): string
{
    $path = tempnam(sys_get_temp_dir(), 'legacy-test-');

    $pdo = new PDO('sqlite:'.$path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec(<<<'SQL'
        CREATE TABLE enrollments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id INTEGER NOT NULL,
            participant_name TEXT NOT NULL,
            participant_email TEXT NOT NULL,
            created_at TEXT NOT NULL
        );
        SQL);

    return $path;
}

afterEach(function () {
    if (isset($this->temporaryLegacyDatabase) && file_exists($this->temporaryLegacyDatabase)) {
        unlink($this->temporaryLegacyDatabase);
    }
});

test('it backfills existing legacy enrollments into real bookings', function () {
    $this->temporaryLegacyDatabase = createTemporaryLegacyDatabase();
    $pdo = new PDO('sqlite:'.$this->temporaryLegacyDatabase);
    $pdo->exec("INSERT INTO enrollments (course_id, participant_name, participant_email, created_at) VALUES (3, 'Ada Lovelace', 'ada@example.com', '2026-01-01 10:00:00')");

    $event = Event::factory()->create(['capacity' => 10]);

    $this->artisan('legacy:migrate-enrollments', [
        'course' => 3,
        'event' => $event->id,
        '--database' => $this->temporaryLegacyDatabase,
    ])->assertSuccessful();

    $this->assertDatabaseHas('bookings', [
        'event_id' => $event->id,
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
        'participant_id' => null,
    ]);
});

test('it migrates every enrollment recorded for the given course, not just one', function () {
    $this->temporaryLegacyDatabase = createTemporaryLegacyDatabase();
    $pdo = new PDO('sqlite:'.$this->temporaryLegacyDatabase);
    $pdo->exec("INSERT INTO enrollments (course_id, participant_name, participant_email, created_at) VALUES (3, 'Ada Lovelace', 'ada@example.com', '2026-01-01 10:00:00')");
    $pdo->exec("INSERT INTO enrollments (course_id, participant_name, participant_email, created_at) VALUES (3, 'Grace Hopper', 'grace@example.com', '2026-01-02 10:00:00')");
    $pdo->exec("INSERT INTO enrollments (course_id, participant_name, participant_email, created_at) VALUES (7, 'Someone Else', 'someone@example.com', '2026-01-03 10:00:00')");

    $event = Event::factory()->create(['capacity' => 10]);

    $this->artisan('legacy:migrate-enrollments', [
        'course' => 3,
        'event' => $event->id,
        '--database' => $this->temporaryLegacyDatabase,
    ])->assertSuccessful();

    $this->assertDatabaseHas('bookings', ['event_id' => $event->id, 'participant_name' => 'Ada Lovelace']);
    $this->assertDatabaseHas('bookings', ['event_id' => $event->id, 'participant_name' => 'Grace Hopper']);
    $this->assertDatabaseMissing('bookings', ['participant_name' => 'Someone Else']);
});
