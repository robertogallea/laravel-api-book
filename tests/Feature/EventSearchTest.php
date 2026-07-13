<?php

use App\Models\Booking;
use App\Models\Event;

test('the events list is paginated', function () {
    Event::factory()->count(20)->create();

    $response = $this->get('/api/v1/events?per_page=5');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
    $response->assertJsonPath('meta.total', 20);
    $response->assertJsonPath('meta.last_page', 4);
    $response->assertJsonStructure(['links' => ['first', 'last', 'prev', 'next'], 'meta']);
});

test('per_page beyond the allowed maximum is rejected', function () {
    $response = $this->get('/api/v1/events?per_page=500');

    $response->assertStatus(422);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJsonPath('code', 'validation_failed');
});

test('the available filter excludes sold-out events', function () {
    $available = Event::factory()->create(['sold_out_at' => null]);
    $soldOut = Event::factory()->create(['sold_out_at' => now()]);

    $response = $this->get('/api/v1/events?available=1');

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($available->id)->not->toContain($soldOut->id);
});

test('the upcoming filter excludes events that have already started', function () {
    $past = Event::factory()->create(['starts_at' => now()->subDay()]);
    $future = Event::factory()->create(['starts_at' => now()->addDay()]);

    $response = $this->get('/api/v1/events?upcoming=1');

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($future->id)->not->toContain($past->id);
});

test('from and to together filter events by a starting date range', function () {
    $inRange = Event::factory()->create(['starts_at' => now()->addDays(5)]);
    $outOfRange = Event::factory()->create(['starts_at' => now()->addMonths(2)]);

    $response = $this->get('/api/v1/events?'.http_build_query([
        'from' => now()->addDays(3)->toIso8601String(),
        'to' => now()->addDays(10)->toIso8601String(),
    ]));

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($inRange->id)->not->toContain($outOfRange->id);
});

test('from without to is rejected instead of silently ignored', function () {
    $response = $this->get('/api/v1/events?from='.now()->toIso8601String());

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('to');
});

test('sorting by most_booked orders events by their booking count and keeps seats_available intact', function () {
    $quiet = Event::factory()->create(['capacity' => 20]);
    $popular = Event::factory()->create(['capacity' => 20]);
    Booking::factory()->create(['event_id' => $quiet->id, 'seats' => 1]);
    Booking::factory()->count(3)->create(['event_id' => $popular->id, 'seats' => 1]);

    $response = $this->get('/api/v1/events?sort=most_booked');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['id'])->toBe($popular->id);
    expect($data[0]['seats_available'])->toBe(17);
});
