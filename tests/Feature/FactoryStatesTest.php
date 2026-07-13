<?php

use App\Models\Booking;
use App\Models\Event;

test('the sold out event state books every seat and marks the event as full', function () {
    $event = Event::factory()->soldOut()->create(['capacity' => 5]);

    expect($event->sold_out_at)->not->toBeNull();
    expect($event->bookings()->sum('seats'))->toBe(5);
    expect(Event::available()->whereKey($event->id)->exists())->toBeFalse();
});

test('the guest booking state has no participant account attached', function () {
    $booking = Booking::factory()->guest()->create();

    expect($booking->participant_id)->toBeNull();
    expect($booking->participant_name)->not->toBeNull();
    expect($booking->participant_email)->not->toBeNull();
});
