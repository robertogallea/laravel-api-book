<?php

test('the health check endpoint responds successfully', function () {
    $response = $this->get('/up');

    $response->assertStatus(200);
});

test('the health check reports up when the database and the queue are reachable', function () {
    $response = $this->get('/api/health');

    $response->assertOk();
    $response->assertJsonPath('data.status', 'up');
    $response->assertJsonPath('data.checks.database', 'ok');
    $response->assertJsonPath('data.checks.queue', 'ok');
});

test('the health check reports the database as down without hiding the queue check', function () {
    // A separate, never-before-resolved connection name: switching database.default to point
    // at it, instead of corrupting the real sqlite connection in place, leaves the transaction
    // RefreshDatabase already opened on it untouched for the rest of the test suite.
    config(['database.connections.broken' => [
        'driver' => 'sqlite',
        // A path inside a directory that does not exist: unlike a missing file, which SQLite
        // creates silently, a missing directory makes the connection attempt fail for real.
        'database' => '/definitely/does/not/exist/database.sqlite',
        'prefix' => '',
    ]]);
    config(['database.default' => 'broken']);

    $response = $this->get('/api/health');

    config(['database.default' => 'sqlite']);

    $response->assertStatus(503);
    $response->assertJsonPath('data.status', 'down');
    $response->assertJsonPath('data.checks.database', 'down');
    $response->assertJsonPath('data.checks.queue', 'ok');
});

test('the health check reports the queue as down without hiding the database check', function () {
    // Tests run with QUEUE_CONNECTION=sync (phpunit.xml), whose size() is a no-op that never
    // fails: switching to the database driver, then pointing it at a table that does not
    // exist, is what actually exercises a real, throwing queue failure.
    config([
        'queue.default' => 'database',
        'queue.connections.database.table' => 'nonexistent_jobs_table',
    ]);

    $response = $this->get('/api/health');

    $response->assertStatus(503);
    $response->assertJsonPath('data.status', 'down');
    $response->assertJsonPath('data.checks.database', 'ok');
    $response->assertJsonPath('data.checks.queue', 'down');
});
