<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('it lists events', function () {
    Event::factory()->count(3)->create();

    $response = $this->get('/api/v1/events');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

test('it creates an event', function () {
    Sanctum::actingAs(User::factory()->organizer()->create());
    $payload = [
        'title' => 'Laravel Conference',
        'description' => 'A conference about Laravel.',
        'starts_at' => now()->addMonth()->toDateTimeString(),
        'capacity' => 100,
    ];

    $response = $this->post('/api/v1/events', $payload);

    $response->assertCreated();
    $this->assertDatabaseHas('events', ['title' => 'Laravel Conference']);
});

test('it shows a single event', function () {
    $event = Event::factory()->create();

    $response = $this->get("/api/v1/events/{$event->id}");

    $response->assertOk();
    $response->assertJsonFragment(['id' => $event->id]);
});

test('it exposes the seats still available and hides internal bookkeeping fields', function () {
    $event = Event::factory()->create(['capacity' => 50]);
    Booking::factory()->create(['event_id' => $event->id, 'seats' => 3]);
    Booking::factory()->create(['event_id' => $event->id, 'seats' => 2]);

    $response = $this->get("/api/v1/events/{$event->id}");

    $response->assertOk();
    $response->assertJsonPath('data.seats_available', 45);
    $response->assertJsonMissingPath('data.updated_at');
});

test('it hides organizer-only fields from an anonymous or non-owning caller', function () {
    $event = Event::factory()->create();
    Booking::factory()->create(['event_id' => $event->id]);

    $response = $this->get("/api/v1/events/{$event->id}");

    $response->assertOk();
    $response->assertJsonMissingPath('data.organizer_id');
    $response->assertJsonMissingPath('data.bookings_count');

    Sanctum::actingAs(User::factory()->create());
    $response = $this->get("/api/v1/events/{$event->id}");

    $response->assertOk();
    $response->assertJsonMissingPath('data.organizer_id');
    $response->assertJsonMissingPath('data.bookings_count');

    // An organizer, just not the one who owns this particular event: EventPolicy::update
    // checks ownership, not only the role, and this is the case that would catch a
    // regression if that ownership check were ever dropped.
    Sanctum::actingAs(User::factory()->organizer()->create());
    $response = $this->get("/api/v1/events/{$event->id}");

    $response->assertOk();
    $response->assertJsonMissingPath('data.organizer_id');
    $response->assertJsonMissingPath('data.bookings_count');
});

test('it exposes organizer-only fields to the event owner and to an admin', function () {
    $organizer = User::factory()->organizer()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    Booking::factory()->count(2)->create(['event_id' => $event->id]);

    Sanctum::actingAs($organizer);
    $response = $this->get("/api/v1/events/{$event->id}");

    $response->assertOk();
    $response->assertJsonPath('data.organizer_id', $organizer->id);
    $response->assertJsonPath('data.bookings_count', 2);

    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->get("/api/v1/events/{$event->id}");

    $response->assertOk();
    $response->assertJsonPath('data.organizer_id', $organizer->id);
    $response->assertJsonPath('data.bookings_count', 2);
});

test('it rejects an incomplete payload when creating an event', function () {
    Sanctum::actingAs(User::factory()->organizer()->create());
    $payload = [
        'description' => 'A conference about Laravel.',
        'starts_at' => now()->addMonth()->toDateTimeString(),
        'capacity' => 100,
    ];

    $response = $this->post('/api/v1/events', $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('title');
});
