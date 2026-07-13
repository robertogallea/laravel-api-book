<?php

use App\Models\Event;

test('the upcoming scope only returns events that have not started yet', function () {
    $past = Event::factory()->create(['starts_at' => now()->subDay()]);
    $future = Event::factory()->create(['starts_at' => now()->addDay()]);

    $upcoming = Event::upcoming()->get();

    expect($upcoming->pluck('id'))
        ->toContain($future->id)
        ->not->toContain($past->id);
});
