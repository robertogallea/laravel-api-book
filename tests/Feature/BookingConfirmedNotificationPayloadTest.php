<?php

use App\Domain\Booking\DataTransferObjects\BookingConfirmedNotificationPayload;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

test('it builds a stable payload from a confirmed booking', function () {
    $event = Event::factory()->create(['title' => 'Laravel Conference']);
    $booking = Booking::factory()->create([
        'event_id' => $event->id,
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 2,
    ]);

    $payload = BookingConfirmedNotificationPayload::fromBooking($booking)->toArray();

    expect($payload)->toBe([
        'event' => 'booking.confirmed',
        'data' => [
            'booking_id' => $booking->id,
            'event_id' => $event->id,
            'event_title' => 'Laravel Conference',
            'participant_name' => 'Ada Lovelace',
            'participant_email' => 'ada@example.com',
            'seats' => 2,
            'confirmed_at' => $booking->created_at->toIso8601String(),
        ],
    ]);
});

test('it loads the event relation explicitly instead of relying on an unguarded lazy load', function () {
    // Model::preventLazyLoading() only ever guards a collection of two or more rows hydrated
    // together (Illuminate\Database\Eloquent\Builder::hydrate()): a single model fetched on
    // its own, like $booking here, never gets that guard, so a bare $booking->event access
    // would silently issue an extra query instead of throwing, not fail loudly. This test
    // fetches $booking on its own, on purpose, to reproduce exactly that single-model case.
    Model::preventLazyLoading();

    $event = Event::factory()->create(['title' => 'Laravel Conference']);
    $created = Booking::factory()->create(['event_id' => $event->id]);
    $booking = Booking::findOrFail($created->id);

    expect($booking->preventsLazyLoading)->toBeFalse();
    expect($booking->relationLoaded('event'))->toBeFalse();

    DB::enableQueryLog();
    $payload = BookingConfirmedNotificationPayload::fromBooking($booking);
    $queries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    expect($payload->eventTitle)->toBe('Laravel Conference');
    expect($booking->relationLoaded('event'))->toBeTrue();
    // Exactly one query to load the relation this DTO needs, not zero (which would mean the
    // assertion above is meaningless) and not more than one (which would mean it reloads a
    // relation that loadMissing() should have skipped).
    expect($queries->filter(fn ($query) => str_contains($query, 'from "events"')))->toHaveCount(1);

    DB::flushQueryLog();
    DB::enableQueryLog();
    BookingConfirmedNotificationPayload::fromBooking($booking);
    $queriesOnSecondCall = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    expect($queriesOnSecondCall->filter(fn ($query) => str_contains($query, 'from "events"')))->toBeEmpty();

    Model::preventLazyLoading(false);
});
