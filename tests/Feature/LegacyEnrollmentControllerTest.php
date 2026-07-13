<?php

use App\Http\Controllers\LegacyEnrollmentController;
use App\Models\Event;

test('it creates a guest booking for a mapped legacy course', function () {
    $event = Event::factory()->create(['id' => 3, 'capacity' => 10]);

    $payload = [
        'course_id' => 3,
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
    ];

    $response = $this->postJson('/api/legacy-enrollments', $payload);

    $response->assertCreated();
    $this->assertDatabaseHas('bookings', [
        'event_id' => $event->id,
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
        'participant_id' => null,
    ]);
});

test('it rejects a course id outside the migration mapping', function () {
    expect(LegacyEnrollmentController::COURSE_TO_EVENT)->not->toHaveKey(999);

    $payload = [
        'course_id' => 999,
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
    ];

    $response = $this->postJson('/api/legacy-enrollments', $payload);

    $response->assertUnprocessable();
    $response->assertJsonPath('code', 'validation_failed');
    $response->assertJsonValidationErrors(['course_id']);
});

test('it does not require authentication', function () {
    $event = Event::factory()->create(['id' => 3, 'capacity' => 10]);

    $response = $this->postJson('/api/legacy-enrollments', [
        'course_id' => 3,
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
    ]);

    $response->assertCreated();
});
