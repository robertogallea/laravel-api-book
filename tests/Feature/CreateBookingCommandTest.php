<?php

use App\Models\Booking;
use App\Models\Event;

test('it creates a booking from the command line', function () {
    $event = Event::factory()->create(['capacity' => 10]);

    $this->artisan('bookings:create', [
        'event' => $event->id,
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 2,
    ])->assertSuccessful();

    $this->assertDatabaseHas('bookings', [
        'event_id' => $event->id,
        'participant_name' => 'Ada Lovelace',
        'seats' => 2,
    ]);
});

test('it rejects a booking exceeding availability from the command line too', function () {
    $event = Event::factory()->create(['capacity' => 1]);
    Booking::factory()->create(['event_id' => $event->id, 'seats' => 1]);

    $this->artisan('bookings:create', [
        'event' => $event->id,
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
    ])->assertFailed();
});
