<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

test('a second request for the same event reuses its cached details instead of fetching the row again', function () {
    $event = Event::factory()->create(['title' => 'Original Title']);

    $this->getJson("/api/v1/events/{$event->id}")->assertOk();

    DB::enableQueryLog();
    $response = $this->getJson("/api/v1/events/{$event->id}");
    $queries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    $response->assertOk();
    $response->assertJsonPath('data.title', 'Original Title');
    // seats_available is deliberately not part of the cached data: it changes with every
    // booking, unlike the rest of an event's details, so it is always summed fresh, one
    // lightweight aggregate query, not the row this test confirms is never fetched again.
    expect($queries)->toHaveCount(1);
    expect($queries->first())->toContain('sum("seats")');
    expect($queries->filter(fn ($query) => str_contains($query, 'from "events"')))->toBeEmpty();
});

test('updating an event invalidates its cached details', function () {
    $organizer = User::factory()->organizer()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id, 'title' => 'Original Title']);

    $this->getJson("/api/v1/events/{$event->id}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Original Title');

    Sanctum::actingAs($organizer);
    $this->putJson("/api/v1/events/{$event->id}", ['title' => 'Updated Title'])->assertOk();

    $this->getJson("/api/v1/events/{$event->id}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated Title');
});

test('uploading a cover image invalidates the cached details', function () {
    Storage::fake('public');
    $organizer = User::factory()->organizer()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $this->getJson("/api/v1/events/{$event->id}")
        ->assertOk()
        ->assertJsonPath('data.cover_image_url', null);

    Sanctum::actingAs($organizer);
    $this->post("/api/v1/events/{$event->id}/cover-image", [
        'cover_image' => UploadedFile::fake()->image('cover.jpg'),
    ])->assertOk();

    $this->getJson("/api/v1/events/{$event->id}")
        ->assertOk()
        ->assertJsonPath('data.cover_image_url', fn ($url) => $url !== null);
});

test('the cache survives a real round trip through the database store, not just the array store tests default to', function () {
    // The test suite runs against the array cache store (phpunit.xml): fast, but it never
    // truly serializes a value, just holds the PHP reference in memory. config/cache.php sets
    // serializable_classes to false, so an Eloquent model handed to the database store, the
    // one this app actually configures, comes back as an unusable __PHP_Incomplete_Class
    // instead of an Event, a failure array-backed tests cannot see. This test forces the real
    // store for this one case, so a regression back to caching the model itself, not its
    // attributes, fails loudly here instead of silently in production.
    config(['cache.default' => 'database']);

    $event = Event::factory()->create(['title' => 'Original Title']);

    $this->getJson("/api/v1/events/{$event->id}")->assertOk();

    $response = $this->getJson("/api/v1/events/{$event->id}");

    $response->assertOk();
    $response->assertJsonPath('data.title', 'Original Title');
});

test('the cached event still reflects each caller\'s own authorization, not whoever populated the cache first', function () {
    $organizer = User::factory()->organizer()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    // An anonymous caller populates the cache first.
    $anonymousResponse = $this->getJson("/api/v1/events/{$event->id}");
    $anonymousResponse->assertOk();
    $anonymousResponse->assertJsonMissingPath('data.organizer_id');
    $anonymousResponse->assertJsonMissingPath('data.bookings_count');

    // The event's own organizer, reading the same now-cached event, still sees their own
    // fields: the cache holds the event's data, not a response already rendered for someone
    // else's permissions.
    Sanctum::actingAs($organizer);
    $organizerResponse = $this->getJson("/api/v1/events/{$event->id}");
    $organizerResponse->assertOk();
    $organizerResponse->assertJsonPath('data.organizer_id', $organizer->id);
    $organizerResponse->assertJsonPath('data.bookings_count', 0);
});
