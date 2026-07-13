<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('v1 still cancels a booking without any cancellation policy, even right before the event starts', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create(['starts_at' => now()->addHour()]);
    $booking = Booking::factory()->create(['event_id' => $event->id, 'participant_id' => $participant->id]);

    $response = $this->delete("/api/v1/bookings/{$booking->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
});

test('v2 cancels a booking when the event is still well in the future', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create(['starts_at' => now()->addWeek()]);
    $booking = Booking::factory()->create(['event_id' => $event->id, 'participant_id' => $participant->id]);

    $response = $this->delete("/api/v2/bookings/{$booking->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
});

test('v2 rejects the cancellation of a booking less than 24 hours before the event starts', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create(['starts_at' => now()->addHours(2)]);
    $booking = Booking::factory()->create(['event_id' => $event->id, 'participant_id' => $participant->id]);

    $response = $this->delete("/api/v2/bookings/{$booking->id}");

    $response->assertStatus(422);
    $response->assertJsonPath('code', 'validation_failed');
    $response->assertJsonValidationErrors('booking');
    $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
});

test('both versions of the booking cancellation endpoint are reachable at the same time', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $upcomingEvent = Event::factory()->create(['starts_at' => now()->addWeek()]);
    $v1Booking = Booking::factory()->create(['event_id' => $upcomingEvent->id, 'participant_id' => $participant->id]);
    $v2Booking = Booking::factory()->create(['event_id' => $upcomingEvent->id, 'participant_id' => $participant->id]);

    $this->delete("/api/v1/bookings/{$v1Booking->id}")->assertNoContent();
    $this->delete("/api/v2/bookings/{$v2Booking->id}")->assertNoContent();
});

test('v2 cancelling the only booking of a sold-out event clears sold_out_at', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create([
        'starts_at' => now()->addWeek(),
        'capacity' => 1,
        'sold_out_at' => now(),
    ]);
    $booking = Booking::factory()->create(['event_id' => $event->id, 'participant_id' => $participant->id, 'seats' => 1]);

    $this->delete("/api/v2/bookings/{$booking->id}")->assertNoContent();

    expect($event->fresh()->sold_out_at)->toBeNull();
});

test('v2 cancelling one of several bookings on a sold-out event also clears sold_out_at once a seat frees up', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create([
        'starts_at' => now()->addWeek(),
        'capacity' => 2,
        'sold_out_at' => now(),
    ]);
    Booking::factory()->create(['event_id' => $event->id, 'seats' => 1]);
    $booking = Booking::factory()->create(['event_id' => $event->id, 'participant_id' => $participant->id, 'seats' => 1]);

    $this->delete("/api/v2/bookings/{$booking->id}")->assertNoContent();

    expect($event->fresh()->sold_out_at)->toBeNull();
});

test('v2 cancelling a booking on an event that was never sold out leaves sold_out_at untouched', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create(['starts_at' => now()->addWeek(), 'capacity' => 10, 'sold_out_at' => null]);
    $booking = Booking::factory()->create(['event_id' => $event->id, 'participant_id' => $participant->id, 'seats' => 1]);

    $this->delete("/api/v2/bookings/{$booking->id}")->assertNoContent();

    expect($event->fresh()->sold_out_at)->toBeNull();
});

test('a cleared sold_out_at makes the event reappear in Event::available() again', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create([
        'starts_at' => now()->addWeek(),
        'capacity' => 1,
        'sold_out_at' => now(),
    ]);
    $booking = Booking::factory()->create(['event_id' => $event->id, 'participant_id' => $participant->id, 'seats' => 1]);

    expect(Event::available()->whereKey($event->id)->exists())->toBeFalse();

    $this->delete("/api/v2/bookings/{$booking->id}")->assertNoContent();

    expect(Event::available()->whereKey($event->id)->exists())->toBeTrue();
});
