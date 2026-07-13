<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('a participant can update the fields this endpoint actually exposes', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create();
    $booking = Booking::factory()->create(['event_id' => $event->id, 'participant_id' => $participant->id]);

    $response = $this->putJson("/api/v1/bookings/{$booking->id}", [
        'participant_name' => 'Updated name',
        'participant_email' => 'updated@example.com',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.participant_name', 'Updated name');
    expect($booking->fresh()->participant_email)->toBe('updated@example.com');
});

test('updating a booking ignores seats, since resizing it is a capacity decision this endpoint does not make', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create(['capacity' => 10]);
    $booking = Booking::factory()->create(['event_id' => $event->id, 'participant_id' => $participant->id, 'seats' => 2]);

    $this->putJson("/api/v1/bookings/{$booking->id}", [
        'participant_name' => 'Updated name',
        'seats' => 999,
    ])->assertOk();

    expect($booking->fresh()->seats)->toBe(2);
});

test('updating a booking ignores event_id, refusing to move it to a different event', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create();
    $otherEvent = Event::factory()->create();
    $booking = Booking::factory()->create(['event_id' => $event->id, 'participant_id' => $participant->id]);

    $this->putJson("/api/v1/bookings/{$booking->id}", [
        'event_id' => $otherEvent->id,
    ])->assertOk();

    expect($booking->fresh()->event_id)->toBe($event->id);
});

test('updating a booking ignores participant_id, refusing to reassign it to a different user', function () {
    $participant = User::factory()->create();
    $otherUser = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create();
    $booking = Booking::factory()->create(['event_id' => $event->id, 'participant_id' => $participant->id]);

    $this->putJson("/api/v1/bookings/{$booking->id}", [
        'participant_id' => $otherUser->id,
    ])->assertOk();

    expect($booking->fresh()->participant_id)->toBe($participant->id);
});
