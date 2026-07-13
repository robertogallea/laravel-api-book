<?php

use App\Models\Event;
use App\Models\IdempotencyKey;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('reusing an idempotency key with a different payload is rejected as a conflict', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 5]);
    $headers = ['Idempotency-Key' => 'reused-with-different-payload'];

    $this->post("/api/v1/events/{$event->id}/bookings", [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
    ], $headers)->assertCreated();

    $conflicting = $this->post("/api/v1/events/{$event->id}/bookings", [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 2, // Same key, different payload: the only thing that changed.
    ], $headers);

    $conflicting->assertStatus(409);
    $conflicting->assertHeader('content-type', 'application/problem+json');
    $conflicting->assertJsonPath('code', 'idempotency_key_conflict');
    $this->assertDatabaseCount('bookings', 1);
});

test('a request with a key still being processed by another request is rejected, not re-executed', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 5]);
    $payload = [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
    ];
    $headers = ['Idempotency-Key' => 'still-in-progress'];

    $this->post("/api/v1/events/{$event->id}/bookings", $payload, $headers)->assertCreated();

    // Roll the stored record back to the state it would be in if the first request had
    // claimed the key but had not finished processing yet: this is exactly what a second,
    // truly concurrent request would find.
    IdempotencyKey::where('key', 'still-in-progress')->update([
        'response_status' => null,
        'response_headers' => null,
        'response_body' => null,
    ]);

    $concurrent = $this->post("/api/v1/events/{$event->id}/bookings", $payload, $headers);

    $concurrent->assertStatus(409);
    $concurrent->assertHeader('content-type', 'application/problem+json');
    $concurrent->assertJsonPath('code', 'idempotency_key_in_progress');
    $this->assertDatabaseCount('bookings', 1);
});
