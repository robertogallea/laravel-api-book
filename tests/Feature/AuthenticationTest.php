<?php

use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('it registers a new user and returns an access token', function () {
    $response = $this->post('/api/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'participant',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.email', 'ada@example.com');
    expect($response->json('meta.token'))->toBeString();
    $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
});

test('it rejects registration with a mismatched password confirmation', function () {
    $response = $this->post('/api/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'something-else',
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('code', 'validation_failed');
    $response->assertJsonValidationErrors('password');
});

test('it logs in an existing user and returns an access token', function () {
    $user = User::factory()->create(['password' => 'password']);

    $response = $this->post('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.email', $user->email);
    expect($response->json('meta.token'))->toBeString();
});

test('it rejects login with the wrong password using the standard error format', function () {
    $user = User::factory()->create(['password' => 'password']);

    $response = $this->post('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJsonPath('code', 'validation_failed');
    $response->assertJsonValidationErrors('email');
});

test('it revokes the current token on logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->post('/api/logout')
        ->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});

test('an unauthenticated request to a protected endpoint fails with the standard error format', function () {
    $event = Event::factory()->create();

    $response = $this->post("/api/v1/events/{$event->id}/bookings", [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
    ]);

    $response->assertStatus(401);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJsonPath('code', 'unauthenticated');
    $response->assertJsonStructure(['type', 'title', 'status', 'detail', 'code']);
});

test('browsing events stays open to anonymous requests', function () {
    Event::factory()->count(2)->create();

    $this->get('/api/v1/events')->assertOk();
});

test('an authenticated user can access a protected endpoint using its issued token', function () {
    Sanctum::actingAs(User::factory()->create());

    $event = Event::factory()->create();

    $response = $this->post("/api/v1/events/{$event->id}/bookings", [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 1,
    ]);

    $response->assertCreated();
});
