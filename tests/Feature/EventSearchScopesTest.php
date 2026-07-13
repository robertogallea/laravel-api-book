<?php

use App\Models\Booking;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

test('the available scope only returns events that are not sold out', function () {
    $available = Event::factory()->create(['sold_out_at' => null]);
    $soldOut = Event::factory()->create(['sold_out_at' => now()]);

    $results = Event::available()->get();

    expect($results->pluck('id'))
        ->toContain($available->id)
        ->not->toContain($soldOut->id);
});

test('the startingBetween scope only returns events starting within the given range', function () {
    $inRange = Event::factory()->create(['starts_at' => now()->addDays(5)]);
    $tooSoon = Event::factory()->create(['starts_at' => now()->addDay()]);
    $tooLate = Event::factory()->create(['starts_at' => now()->addMonths(2)]);

    $results = Event::startingBetween(now()->addDays(3), now()->addDays(10))->get();

    expect($results->pluck('id'))
        ->toContain($inRange->id)
        ->not->toContain($tooSoon->id)
        ->not->toContain($tooLate->id);
});

test('scopes compose to narrow a search across multiple conditions', function () {
    $match = Event::factory()->create([
        'starts_at' => now()->addDays(5),
        'sold_out_at' => null,
    ]);
    $wrongDate = Event::factory()->create([
        'starts_at' => now()->addMonths(2),
        'sold_out_at' => null,
    ]);
    $full = Event::factory()->create([
        'starts_at' => now()->addDays(5),
        'sold_out_at' => now(),
    ]);

    $results = Event::upcoming()
        ->available()
        ->startingBetween(now()->addDays(3), now()->addDays(10))
        ->get();

    expect($results->pluck('id'))
        ->toContain($match->id)
        ->not->toContain($wrongDate->id)
        ->not->toContain($full->id);
});

test('the mostBooked scope orders events by their booking count without loading every booking row', function () {
    $quiet = Event::factory()->create();
    $popular = Event::factory()->create();
    Booking::factory()->count(1)->create(['event_id' => $quiet->id]);
    Booking::factory()->count(5)->create(['event_id' => $popular->id]);

    DB::enableQueryLog();
    $results = Event::mostBooked()->get();
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($results->pluck('id')->take(2)->all())->toBe([$popular->id, $quiet->id]);
    // A single query with an aggregate subquery, not a second one eager loading every
    // booking row: without('bookings') is what keeps this at 1 instead of 2.
    expect($queryCount)->toBe(1);
});
