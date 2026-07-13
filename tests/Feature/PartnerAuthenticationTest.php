<?php

use App\Models\User;
use Laravel\Passport\Client;

test('a partner exchanges its client credentials for an access token and calls a protected endpoint', function () {
    $client = Client::factory()->asClientCredentials()->create(['name' => 'Acme Resale']);

    $tokenResponse = $this->post('/oauth/token', [
        'grant_type' => 'client_credentials',
        'client_id' => $client->id,
        'client_secret' => $client->plainSecret,
    ]);

    $tokenResponse->assertOk();
    $accessToken = $tokenResponse->json('access_token');
    expect($accessToken)->toBeString();

    $response = $this->withHeader('Authorization', "Bearer {$accessToken}")
        ->get('/api/partner/ping');

    $response->assertOk();
    $response->assertJsonPath('data.authenticated_as', 'Acme Resale');
});

test('a partner endpoint rejects requests without a valid Passport token', function () {
    $response = $this->get('/api/partner/ping');

    $response->assertStatus(401);
    $response->assertJsonPath('code', 'unauthenticated');
});

test('a Sanctum token issued for an end user does not grant access to a partner endpoint', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->get('/api/partner/ping');

    $response->assertStatus(401);
});
