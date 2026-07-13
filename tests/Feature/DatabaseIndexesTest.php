<?php

use Illuminate\Support\Facades\Schema;

test('events has an index on starts_at, used by the upcoming and startingBetween scopes', function () {
    $columns = collect(Schema::getIndexes('events'))->flatMap(fn ($index) => $index['columns']);

    expect($columns)->toContain('starts_at');
});

test('bookings has an index on event_id, the foreign key used to look up an event\'s bookings', function () {
    $columns = collect(Schema::getIndexes('bookings'))->flatMap(fn ($index) => $index['columns']);

    expect($columns)->toContain('event_id');
});
