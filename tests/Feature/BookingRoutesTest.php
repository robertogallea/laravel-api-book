<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('it lists the bookings of an event', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    Booking::factory()->count(2)->create(['event_id' => $event->id]);

    $response = $this->get("/api/v1/events/{$event->id}/bookings");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

test('it creates a booking for an event', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create();

    $payload = [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 2,
    ];

    $response = $this->post("/api/v1/events/{$event->id}/bookings", $payload);

    $response->assertCreated();
    $this->assertDatabaseHas('bookings', ['event_id' => $event->id, 'participant_name' => 'Ada Lovelace']);
});

test('it rejects a booking missing its required fields', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create();

    $response = $this->post("/api/v1/events/{$event->id}/bookings", []);

    $response->assertUnprocessable();
    $response->assertJsonPath('code', 'validation_failed');
    $response->assertJsonValidationErrors(['participant_name', 'participant_email', 'seats']);
});

test('it returns the newly created booking with the documented response structure', function () {
    Sanctum::actingAs($participant = User::factory()->create());
    $event = Event::factory()->create();

    $payload = [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 2,
    ];

    $response = $this->post("/api/v1/events/{$event->id}/bookings", $payload);

    $response->assertCreated();
    $response->assertJsonStructure([
        'data' => ['id', 'event_id', 'participant_name', 'participant_email', 'seats', 'created_at', 'participant' => ['id', 'role']],
    ]);
    $response->assertJsonPath('data.participant.id', $participant->id);
});

test('it rejects a booking that exceeds the seats available for the event', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 5]);
    Booking::factory()->create(['event_id' => $event->id, 'seats' => 4]);

    $payload = [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 2,
    ];

    $response = $this->post("/api/v1/events/{$event->id}/bookings", $payload);

    $response->assertUnprocessable();
    $response->assertJsonPath('code', 'validation_failed');
    $response->assertJsonValidationErrors('seats');
});

test('it shows a single booking without nesting it under its event', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $booking = Booking::factory()->create(['participant_id' => $participant->id]);

    $response = $this->get("/api/v1/bookings/{$booking->id}");

    $response->assertOk();
    $response->assertJsonFragment(['id' => $booking->id]);
});
