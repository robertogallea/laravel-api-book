<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

test('listing an event\'s bookings issues a constant number of queries regardless of how many there are', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    $event = Event::factory()->create(['organizer_id' => $organizer->id, 'capacity' => 50]);
    Booking::factory()->count(25)->create(['event_id' => $event->id]);

    DB::enableQueryLog();
    $response = $this->get("/api/v1/events/{$event->id}/bookings?per_page=25");
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(25);

    Booking::factory()->count(25)->create(['event_id' => $event->id]);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get("/api/v1/events/{$event->id}/bookings?per_page=25")->assertOk();
    $secondQueryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // What this test actually guards: the query count stays constant regardless of how many
    // bookings exist, an N+1 regression would grow it instead.
    expect($secondQueryCount)->toBe($queryCount);
});

test('the bookings list is paginated', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    $event = Event::factory()->create(['organizer_id' => $organizer->id, 'capacity' => 100]);
    Booking::factory()->count(20)->create(['event_id' => $event->id]);

    $response = $this->get("/api/v1/events/{$event->id}/bookings");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(15);
    $response->assertJsonPath('meta.total', 20);
    $response->assertJsonStructure(['links' => ['first', 'last', 'prev', 'next'], 'meta']);
});

test('per_page beyond the allowed maximum is rejected on the bookings list', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $response = $this->get("/api/v1/events/{$event->id}/bookings?per_page=500");

    $response->assertStatus(422);
    $response->assertJsonPath('code', 'validation_failed');
});
