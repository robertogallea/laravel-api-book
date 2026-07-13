<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

test('it uploads a cover image for an event', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    Storage::fake('public');
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $response = $this->post("/api/v1/events/{$event->id}/cover-image", [
        'cover_image' => UploadedFile::fake()->image('cover.jpg', 800, 600),
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.cover_image_url', fn ($url) => str_contains($url, '/storage/event-covers/'));

    $path = $event->fresh()->cover_image_path;
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

test('it discards the original file name to avoid unexpected characters or encoding', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    Storage::fake('public');
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $this->post("/api/v1/events/{$event->id}/cover-image", [
        'cover_image' => UploadedFile::fake()->image('évènement café 日本語.jpg', 400, 400),
    ])->assertOk();

    $path = $event->fresh()->cover_image_path;
    expect($path)->not->toContain('évènement');
});

test('it rejects a cover image with a disallowed format using the standard error format', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    Storage::fake('public');
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $response = $this->post("/api/v1/events/{$event->id}/cover-image", [
        'cover_image' => UploadedFile::fake()->create('notes.txt', 10),
    ]);

    $response->assertStatus(422);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJson(['code' => 'validation_failed']);
    $response->assertJsonValidationErrors('cover_image');
});

test('it rejects a cover image that is too large using the standard error format', function () {
    $organizer = User::factory()->organizer()->create();
    Sanctum::actingAs($organizer);
    Storage::fake('public');
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $response = $this->post("/api/v1/events/{$event->id}/cover-image", [
        'cover_image' => UploadedFile::fake()->image('cover.jpg')->size(3000),
    ]);

    $response->assertStatus(422);
    $response->assertJson(['code' => 'validation_failed']);
    $response->assertJsonValidationErrors('cover_image');
});
