<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('an organizer can update the fields this endpoint actually exposes', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    $event = Event::factory()->create(['organizer_id' => $organizer->id, 'title' => 'Original title']);

    $response = $this->putJson("/api/v1/events/{$event->id}", [
        'title' => 'Updated title',
        'description' => 'Updated description',
        'location' => 'Updated location',
        'starts_at' => now()->addMonth()->toIso8601String(),
        'capacity' => 42,
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.title', 'Updated title');
    expect($event->fresh()->capacity)->toBe(42);
});

test('updating an event ignores organizer_id, refusing to reassign ownership through a generic update', function () {
    $organizer = User::factory()->organizer()->create();
    $otherOrganizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $this->putJson("/api/v1/events/{$event->id}", [
        'title' => 'Updated title',
        'organizer_id' => $otherOrganizer->id,
    ])->assertOk();

    expect($event->fresh()->organizer_id)->toBe($organizer->id);
});

test('updating an event ignores sold_out_at, since it is only ever set or cleared by the booking domain', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    $soldOutAt = now()->subDay();
    $event = Event::factory()->create(['organizer_id' => $organizer->id, 'sold_out_at' => $soldOutAt]);

    $this->putJson("/api/v1/events/{$event->id}", [
        'title' => 'Updated title',
        'sold_out_at' => null,
    ])->assertOk();

    expect($event->fresh()->sold_out_at)->not->toBeNull();
});

test('updating an event ignores cover_image_path, which has its own dedicated endpoint', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    $event = Event::factory()->create(['organizer_id' => $organizer->id, 'cover_image_path' => null]);

    $this->putJson("/api/v1/events/{$event->id}", [
        'title' => 'Updated title',
        'cover_image_path' => 'event-covers/hijacked.jpg',
    ])->assertOk();

    expect($event->fresh()->cover_image_path)->toBeNull();
});

test('shrinking capacity below the seats already booked is rejected', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    $event = Event::factory()->create(['organizer_id' => $organizer->id, 'capacity' => 10]);
    Booking::factory()->create(['event_id' => $event->id, 'seats' => 7]);

    $response = $this->putJson("/api/v1/events/{$event->id}", ['capacity' => 5]);

    $response->assertStatus(422);
    $response->assertJsonPath('code', 'validation_failed');
    $response->assertJsonValidationErrors('capacity');
    expect($event->fresh()->capacity)->toBe(10);
});

test('shrinking capacity down to exactly the seats already booked is accepted', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    $event = Event::factory()->create(['organizer_id' => $organizer->id, 'capacity' => 10]);
    Booking::factory()->create(['event_id' => $event->id, 'seats' => 7]);

    $this->putJson("/api/v1/events/{$event->id}", ['capacity' => 7])->assertOk();

    expect($event->fresh()->capacity)->toBe(7);
});
