<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('an existing client that omits the new optional event location keeps working', function () {
    Sanctum::actingAs(User::factory()->organizer()->create());
    $payload = [
        'title' => 'Laravel Conference',
        'description' => 'A conference about Laravel.',
        'starts_at' => now()->addMonth()->toDateTimeString(),
        'capacity' => 100,
    ];

    $response = $this->post('/api/v1/events', $payload);

    $response->assertCreated();
    $response->assertJsonPath('data.location', null);
});

test('a client can opt in to the new optional event location', function () {
    Sanctum::actingAs(User::factory()->organizer()->create());
    $payload = [
        'title' => 'Laravel Conference',
        'description' => 'A conference about Laravel.',
        'location' => 'Palermo, Italy',
        'starts_at' => now()->addMonth()->toDateTimeString(),
        'capacity' => 100,
    ];

    $response = $this->post('/api/v1/events', $payload);

    $response->assertCreated();
    $response->assertJsonPath('data.location', 'Palermo, Italy');
});
