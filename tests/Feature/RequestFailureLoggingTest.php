<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    File::put(storage_path('logs/structured.log'), '');
});

test('a failed request is logged with structured context on the dedicated channel', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 5]);
    Booking::factory()->create(['event_id' => $event->id, 'seats' => 4]);

    $payload = [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 2,
    ];

    $response = $this->post("/api/v1/events/{$event->id}/bookings", $payload);

    $response->assertUnprocessable();

    $lines = array_values(array_filter(explode("\n", File::get(storage_path('logs/structured.log')))));
    expect($lines)->toHaveCount(1);

    $entry = json_decode($lines[0], true);
    expect($entry['message'])->toBe('request.failed');
    expect($entry['context']['method'])->toBe('POST');
    expect($entry['context']['path'])->toBe("api/v1/events/{$event->id}/bookings");
    expect($entry['context']['status'])->toBe(422);
    expect($entry['context']['code'])->toBe('validation_failed');
    expect($entry['context']['detail'])->toContain('seat');
});

test('a successful request produces no structured log entry', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create();

    $payload = [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 2,
    ];

    $response = $this->post("/api/v1/events/{$event->id}/bookings", $payload);

    $response->assertCreated();

    $lines = array_values(array_filter(explode("\n", File::get(storage_path('logs/structured.log')))));
    expect($lines)->toBeEmpty();
});
