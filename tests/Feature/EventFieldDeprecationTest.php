<?php

use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('a response including the deprecated location field signals it via Deprecation and Link headers', function () {
    $event = Event::factory()->create();

    $response = $this->get("/api/v1/events/{$event->id}");

    $response->assertOk();
    $response->assertHeader('Deprecation', 'true');
    $response->assertHeader('Sunset', 'Sun, 04 Oct 2026 00:00:00 GMT');
    $response->assertHeader('Link', '<https://eventhub.test/deprecations/event-location>; rel="deprecation"');
});

test('the field deprecation applies the same way on v2, since it is not tied to any version', function () {
    $event = Event::factory()->create();

    $response = $this->get("/api/v2/events/{$event->id}");

    $response->assertOk();
    $response->assertHeader('Deprecation', 'true');
    $response->assertHeader('Link', '<https://eventhub.test/deprecations/event-location>; rel="deprecation"');
});

test('deleting an event does not carry the field-level deprecation, since its response has no body', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $response = $this->delete("/api/v1/events/{$event->id}");

    $response->assertNoContent();
    $response->assertHeaderMissing('Deprecation');
    $response->assertHeaderMissing('Link');
});
