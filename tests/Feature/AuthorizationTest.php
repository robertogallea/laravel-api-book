<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('a participant cannot update an event, regardless of who owns it', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create();

    $response = $this->put("/api/v1/events/{$event->id}", ['title' => 'Hijacked title']);

    $response->assertStatus(403);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJsonPath('code', 'forbidden');
    $response->assertJsonStructure(['type', 'title', 'status', 'detail', 'code']);
    $this->assertDatabaseMissing('events', ['id' => $event->id, 'title' => 'Hijacked title']);
});

test('an organizer cannot update an event owned by another organizer', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    $someoneElsesEvent = Event::factory()->create();

    $response = $this->put("/api/v1/events/{$someoneElsesEvent->id}", ['title' => 'Hijacked title']);

    $response->assertStatus(403);
    $response->assertJsonPath('code', 'forbidden');
    $this->assertDatabaseMissing('events', ['id' => $someoneElsesEvent->id, 'title' => 'Hijacked title']);
});

test('an organizer can update its own event', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $response = $this->put("/api/v1/events/{$event->id}", ['title' => 'Updated title']);

    $response->assertOk();
    $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Updated title']);
});

test('an admin can update an event owned by any organizer', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $event = Event::factory()->create();

    $response = $this->put("/api/v1/events/{$event->id}", ['title' => 'Updated by admin']);

    $response->assertOk();
    $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Updated by admin']);
});

test('a participant cannot view a booking made by another participant', function () {
    Sanctum::actingAs(User::factory()->create());
    $someoneElsesBooking = Booking::factory()->create();

    $response = $this->get("/api/v1/bookings/{$someoneElsesBooking->id}");

    $response->assertStatus(403);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJsonPath('code', 'forbidden');
    $response->assertJsonStructure(['type', 'title', 'status', 'detail', 'code']);
});

test('a participant can view its own booking', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $booking = Booking::factory()->create(['participant_id' => $participant->id]);

    $response = $this->get("/api/v1/bookings/{$booking->id}");

    $response->assertOk();
    $response->assertJsonPath('data.id', $booking->id);
});

test('an admin can view a booking owned by any participant', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $booking = Booking::factory()->create();

    $response = $this->get("/api/v1/bookings/{$booking->id}");

    $response->assertOk();
    $response->assertJsonPath('data.id', $booking->id);
});

test('a participant cannot update a booking made by another participant', function () {
    Sanctum::actingAs(User::factory()->create());
    $someoneElsesBooking = Booking::factory()->create();

    $response = $this->put("/api/v1/bookings/{$someoneElsesBooking->id}", ['participant_name' => 'Hijacked name']);

    $response->assertStatus(403);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJsonPath('code', 'forbidden');
    $this->assertDatabaseMissing('bookings', ['id' => $someoneElsesBooking->id, 'participant_name' => 'Hijacked name']);
});

test('a participant can update its own booking', function () {
    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $booking = Booking::factory()->create(['participant_id' => $participant->id]);

    $response = $this->put("/api/v1/bookings/{$booking->id}", ['participant_name' => 'Updated name']);

    $response->assertOk();
    $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'participant_name' => 'Updated name']);
});

test('an admin can update a booking owned by any participant', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $booking = Booking::factory()->create();

    $response = $this->put("/api/v1/bookings/{$booking->id}", ['participant_name' => 'Updated by admin']);

    $response->assertOk();
    $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'participant_name' => 'Updated by admin']);
});

test('a participant cannot cancel a booking made by another participant', function () {
    Sanctum::actingAs(User::factory()->create());
    $someoneElsesBooking = Booking::factory()->create();

    $response = $this->delete("/api/v1/bookings/{$someoneElsesBooking->id}");

    $response->assertStatus(403);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJsonPath('code', 'forbidden');
    $response->assertJsonStructure(['type', 'title', 'status', 'detail', 'code']);
    $this->assertDatabaseHas('bookings', ['id' => $someoneElsesBooking->id]);
});

test('an admin can cancel a booking owned by any participant', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $booking = Booking::factory()->create();

    $response = $this->delete("/api/v1/bookings/{$booking->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
});

test('an organizer cannot list the bookings of an event it does not own', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    $someoneElsesEvent = Event::factory()->create();

    $response = $this->get("/api/v1/events/{$someoneElsesEvent->id}/bookings");

    $response->assertStatus(403);
    $response->assertJsonPath('code', 'forbidden');
});
