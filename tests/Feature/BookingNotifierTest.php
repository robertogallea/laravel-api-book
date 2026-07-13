<?php

use App\Domain\Booking\Contracts\BookingNotifier;
use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('it notifies the bound BookingNotifier when a booking is created', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 5]);

    $notifier = Mockery::mock(BookingNotifier::class);
    $notifier->shouldReceive('notifyCreated')->once();
    $this->app->instance(BookingNotifier::class, $notifier);

    $payload = [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 2,
    ];

    $this->post("/api/v1/events/{$event->id}/bookings", $payload)->assertCreated();
});
