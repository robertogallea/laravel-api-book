<?php

use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('booking creation is throttled after 5 requests per minute for the same user', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 10]);

    for ($i = 0; $i < 5; $i++) {
        $response = $this->post("/api/v1/events/{$event->id}/bookings", [
            'participant_name' => 'Ada Lovelace',
            'participant_email' => 'ada@example.com',
            'seats' => 1,
        ]);

        $response->assertCreated();
    }

    $response = $this->post("/api/v1/events/{$event->id}/bookings", [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
    ]);

    $response->assertStatus(429);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJsonPath('code', 'too_many_requests');
    $response->assertJsonStructure(['type', 'title', 'status', 'detail', 'code']);
    expect($response->headers->has('Retry-After'))->toBeTrue();
});

test('browsing the events catalog tolerates far more requests per minute than creating a booking', function () {
    Event::factory()->count(2)->create();

    for ($i = 0; $i < 30; $i++) {
        $this->get('/api/v1/events')->assertOk();
    }

    $response = $this->get('/api/v1/events');

    $response->assertStatus(429);
    $response->assertJsonPath('code', 'too_many_requests');
});
