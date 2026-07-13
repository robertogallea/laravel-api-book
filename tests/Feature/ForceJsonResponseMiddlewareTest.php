<?php

test('a request without an Accept header still receives a JSON response', function () {
    $response = $this->get('/api/v1/events');

    $response->assertHeader('content-type', 'application/json');
});

test('a request without an Accept header still receives a JSON error response', function () {
    $response = $this->get('/api/v1/events/999');

    $response->assertStatus(404);
    $response->assertHeader('content-type', 'application/problem+json');
});
