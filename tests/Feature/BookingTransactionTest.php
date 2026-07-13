<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

afterEach(fn () => Event::flushEventListeners());

test('it marks an event as sold out when its last available seat is booked', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 1]);

    $this->post("/api/v1/events/{$event->id}/bookings", [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
    ])->assertCreated();

    expect($event->fresh()->sold_out_at)->not->toBeNull();
});

test('a transaction is rolled back when the sold-out update fails mid-operation', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 1]);

    Event::updating(fn () => throw new RuntimeException('Simulated failure to test rollback.'));

    $response = $this->post("/api/v1/events/{$event->id}/bookings", [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
    ]);

    $response->assertStatus(500);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJsonPath('code', 'server_error');
    $this->assertDatabaseCount('bookings', 0);
    $this->assertDatabaseHas('events', ['id' => $event->id, 'sold_out_at' => null]);
});

test('creating a booking sums existing bookings with an aggregate instead of loading every row', function () {
    // CreateBookingAction dispatches a queued booking-confirmation notification: under the
    // sync queue driver this suite otherwise runs on, that job would execute inline,
    // in this same request, adding queries of its own (re-fetching the booking, lazy-loading
    // its event) that have nothing to do with the transactional query budget this test checks.
    // A real queue connection defers that work to a separate worker, at a separate time, so
    // faking the queue here isolates the assertion to what this request actually does.
    Queue::fake();

    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 1000]);
    Booking::factory()->count(50)->create(['event_id' => $event->id, 'seats' => 1]);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->postJson("/api/v1/events/{$event->id}/bookings", [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
    ])->assertCreated();
    $queries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    // Route model binding's own eager load of $event->bookings (Event::$with) accounts for
    // the one full-row read expected here: a regression inside CreateBookingAction would add
    // a second one for the locked re-fetch, instead of the aggregate this test also checks for.
    expect($queries->filter(fn ($query) => str_contains($query, 'select * from "bookings"')))->toHaveCount(1);
    expect($queries->filter(fn ($query) => str_contains($query, 'sum(')))->toHaveCount(1);
});
