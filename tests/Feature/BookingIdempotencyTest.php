<?php

use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('a repeated request with the same idempotency key does not create a second booking', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 5]);

    $payload = [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 2,
    ];
    $headers = ['Idempotency-Key' => 'a1b2c3'];

    $first = $this->post("/api/v1/events/{$event->id}/bookings", $payload, $headers);
    $second = $this->post("/api/v1/events/{$event->id}/bookings", $payload, $headers);

    $first->assertCreated();
    $second->assertCreated();
    expect($second->json('data.id'))->toBe($first->json('data.id'));
    $this->assertDatabaseCount('bookings', 1);
});

test('two requests without an idempotency key each create their own booking', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 5]);

    $payload = [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
    ];

    $this->post("/api/v1/events/{$event->id}/bookings", $payload)->assertCreated();
    $this->post("/api/v1/events/{$event->id}/bookings", $payload)->assertCreated();

    $this->assertDatabaseCount('bookings', 2);
});

test('a different idempotency key creates a second, independent booking', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 5]);

    $payload = [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
    ];

    $this->post("/api/v1/events/{$event->id}/bookings", $payload, ['Idempotency-Key' => 'key-one'])
        ->assertCreated();
    $this->post("/api/v1/events/{$event->id}/bookings", $payload, ['Idempotency-Key' => 'key-two'])
        ->assertCreated();

    $this->assertDatabaseCount('bookings', 2);
});

test('the stored response reproduces the same content type as the original', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 5]);

    $payload = [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
    ];
    $headers = ['Idempotency-Key' => 'same-content-type'];

    $first = $this->post("/api/v1/events/{$event->id}/bookings", $payload, $headers);
    $second = $this->post("/api/v1/events/{$event->id}/bookings", $payload, $headers);

    expect($second->headers->get('content-type'))->toBe($first->headers->get('content-type'));
});
