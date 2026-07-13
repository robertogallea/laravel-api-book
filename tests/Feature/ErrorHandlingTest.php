<?php

use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('a validation error on booking creation uses the standard problem details format', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create();

    $response = $this->post("/api/v1/events/{$event->id}/bookings", [
        'participant_email' => 'not-an-email',
    ]);

    $response->assertStatus(422);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJson([
        'status' => 422,
        'code' => 'validation_failed',
    ]);
    $response->assertJsonStructure(['type', 'title', 'status', 'detail', 'code', 'errors']);
    $response->assertJsonValidationErrors(['participant_name', 'participant_email', 'seats']);
});

test('a missing resource uses the standard problem details format', function () {
    $response = $this->get('/api/v1/events/999999');

    $response->assertStatus(404);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJson([
        'status' => 404,
        'code' => 'resource_not_found',
    ]);
    $response->assertJsonStructure(['type', 'title', 'status', 'detail', 'code']);
});
