<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('a client retrying after not receiving the response gets the already created booking back', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 5]);

    $payload = [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
    ];
    $headers = ['Idempotency-Key' => 'client-retry-after-timeout'];

    // The original request: from the server's point of view, this is indistinguishable from
    // any other request. It completes normally, the booking is created, and the response is
    // stored under this key. Whatever happens to it afterwards, a dropped connection, a client
    // that gave up waiting, is a fact about the network the server never learns about.
    $original = $this->post("/api/v1/events/{$event->id}/bookings", $payload, $headers);
    $original->assertCreated();

    // The client never saw that response and, believing the request failed, retries it with
    // the same key it generated the first time.
    $retry = $this->post("/api/v1/events/{$event->id}/bookings", $payload, $headers);

    $retry->assertCreated();
    expect($retry->json('data.id'))->toBe($original->json('data.id'));
    $this->assertDatabaseCount('bookings', 1);
});

test('a request that never reached the server is processed normally when the client retries it', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 5]);

    $payload = [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
    ];

    // No prior attempt ever reached EventHub for this key: from the server's perspective this
    // retry is the first time the key has ever been seen, and it is handled exactly like any
    // other new request, no special casing needed.
    $retry = $this->post("/api/v1/events/{$event->id}/bookings", $payload, [
        'Idempotency-Key' => 'request-lost-before-arrival',
    ]);

    $retry->assertCreated();
    $this->assertDatabaseCount('bookings', 1);
    expect(Booking::first()->id)->toBe($retry->json('data.id'));
});
