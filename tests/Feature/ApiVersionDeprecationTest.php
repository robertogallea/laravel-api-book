<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('the deprecated v1 booking cancellation endpoint still works and signals its own deprecation', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create(['starts_at' => now()->addHour()]);
    $booking = Booking::factory()->create(['event_id' => $event->id, 'participant_id' => $participant->id]);

    $response = $this->delete("/api/v1/bookings/{$booking->id}");

    $response->assertNoContent();
    $response->assertHeader('Deprecation', 'true');
    $response->assertHeader('Sunset', 'Sun, 04 Oct 2026 00:00:00 GMT');
});

test('the v2 booking cancellation endpoint is not flagged as deprecated', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create(['starts_at' => now()->addWeek()]);
    $booking = Booking::factory()->create(['event_id' => $event->id, 'participant_id' => $participant->id]);

    $response = $this->delete("/api/v2/bookings/{$booking->id}");

    $response->assertNoContent();
    $response->assertHeaderMissing('Deprecation');
    $response->assertHeaderMissing('Sunset');
});

test('calling a removed API version fails with the standard problem details format', function () {
    $response = $this->get('/api/v0/events');

    $response->assertStatus(410);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJson([
        'status' => 410,
        'code' => 'api_version_removed',
    ]);
    $response->assertJsonStructure(['type', 'title', 'status', 'detail', 'code']);
});

test('calling the API without specifying any version at all is a plain not-found, not a removed-version error', function () {
    $response = $this->get('/api/events');

    $response->assertStatus(404);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJson([
        'status' => 404,
        'code' => 'resource_not_found',
    ]);
});
