<?php

use App\Models\User;
use Illuminate\Support\Once;
use Laravel\Passport\Client;
use Laravel\Passport\Token;

test('an expired Sanctum token is rejected on a protected endpoint, in the standard error format', function () {
    $user = User::factory()->create();
    $newToken = $user->createToken('api');
    $newToken->accessToken->forceFill(['created_at' => now()->subDays(31)])->save();

    $response = $this->withHeader('Authorization', "Bearer {$newToken->plainTextToken}")
        ->post('/api/logout');

    $response->assertStatus(401);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJsonPath('code', 'unauthenticated');
    $response->assertJsonStructure(['type', 'title', 'status', 'detail', 'code']);
});

test('an expired Sanctum token produces a response identical to a token that never existed', function () {
    $user = User::factory()->create();
    $newToken = $user->createToken('api');
    $newToken->accessToken->forceFill(['created_at' => now()->subDays(31)])->save();

    $expiredResponse = $this->withHeader('Authorization', "Bearer {$newToken->plainTextToken}")
        ->post('/api/logout');

    $madeUpResponse = $this->withHeader('Authorization', 'Bearer 999|this-token-was-never-issued')
        ->post('/api/logout');

    expect($expiredResponse->status())->toBe($madeUpResponse->status());
    expect($expiredResponse->json())->toEqual($madeUpResponse->json());
});

test('a token revoked through logout no longer authenticates any later request', function () {
    $user = User::factory()->create();
    $newToken = $user->createToken('api');

    // Same effect logout has on the token it revokes (AuthController::logout): once deleted,
    // the plain text token a client might still be holding no longer resolves to anyone.
    $newToken->accessToken->delete();

    $response = $this->withHeader('Authorization', "Bearer {$newToken->plainTextToken}")->post('/api/logout');

    $response->assertStatus(401);
    $response->assertJsonPath('code', 'unauthenticated');
});

test('a revoked Passport token no longer authenticates the partner endpoint', function () {
    $client = Client::factory()->asClientCredentials()->create(['name' => 'Acme Resale']);

    $tokenResponse = $this->post('/oauth/token', [
        'grant_type' => 'client_credentials',
        'client_id' => $client->id,
        'client_secret' => $client->plainSecret,
    ]);
    $accessToken = $tokenResponse->json('access_token');

    // CheckToken (the `client` middleware) checks exactly this flag on every request: revoking
    // the token is what it takes to block it, regardless of which endpoint or controller code
    // is behind the middleware.
    Token::where('client_id', $client->id)->first()->revoke();

    $response = $this->withHeader('Authorization', "Bearer {$accessToken}")->get('/api/partner/ping');

    $response->assertStatus(401);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJsonPath('code', 'unauthenticated');
});

test('revoking only a partner client, without revoking its token, still fails cleanly on an endpoint that checks the client', function () {
    $client = Client::factory()->asClientCredentials()->create(['name' => 'Acme Resale']);

    $tokenResponse = $this->post('/oauth/token', [
        'grant_type' => 'client_credentials',
        'client_id' => $client->id,
        'client_secret' => $client->plainSecret,
    ]);
    $accessToken = $tokenResponse->json('access_token');

    // The token itself is left untouched: CheckToken alone would still accept it. What fails
    // it here is PartnerController::ping asking Auth::guard('api')->client(), which resolves
    // to null for a revoked client (ClientRepository::findActive filters it out).
    $client->forceFill(['revoked' => true])->save();

    // ClientRepository::find() memoizes its result via Laravel's once(), which already ran
    // once for this client during the /oauth/token exchange above: without flushing it here,
    // this test would keep seeing the pre-revocation client for the rest of this test, not a
    // fresh lookup. Laravel's own TestCase flushes this between tests, just not mid-test.
    Once::flush();

    $response = $this->withHeader('Authorization', "Bearer {$accessToken}")->get('/api/partner/ping');

    $response->assertStatus(401);
    $response->assertHeader('content-type', 'application/problem+json');
    $response->assertJsonPath('code', 'unauthenticated');
});
