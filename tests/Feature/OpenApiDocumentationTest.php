<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

test('the generated OpenAPI document describes the booking creation endpoint from existing code', function () {
    $this->withoutMiddleware(RestrictedDocsAccess::class);

    $response = $this->get('/docs/api.json');

    $response->assertOk();

    $operation = $response->json('paths./v1/events/{event}/bookings.post');

    expect($operation['requestBody']['content']['application/json']['schema'])
        ->toBe(['$ref' => '#/components/schemas/StoreBookingRequest'])
        ->and($operation['responses']['422'])
        ->toBe(['$ref' => '#/components/responses/ValidationException']);

    $requestSchema = $response->json('components.schemas.StoreBookingRequest');

    expect($requestSchema['required'])->toEqual(['participant_name', 'participant_email', 'seats']);
});

test('the documented validation error matches EventHub\'s real Problem Details envelope', function () {
    $this->withoutMiddleware(RestrictedDocsAccess::class);

    $response = $this->get('/docs/api.json');

    $schema = $response->json('components.responses.ValidationException.content.application/problem+json.schema');

    expect($schema['required'])->toEqual(['type', 'title', 'status', 'detail', 'code'])
        ->and($schema['properties'])->toHaveKey('errors');

    expect($response->json('components.responses.ValidationException.content'))
        ->toHaveKey('application/problem+json')
        ->and($response->json('components.responses.ValidationException.content'))
        ->not->toHaveKey('application/json');
});

test('the default OpenAPI document does not mark the partner ping endpoint as public', function () {
    $this->withoutMiddleware(RestrictedDocsAccess::class);

    $response = $this->get('/docs/api.json');

    // No explicit "security": [] override here means the operation inherits the document's
    // global security requirement instead of being marked public: the `client` middleware
    // alias (config/scramble.php security_strategy) is what makes that happen.
    expect($response->json('paths./partner/ping.get'))->not->toHaveKey('security');
});

test('the partner OpenAPI document only describes the partner-facing routes', function () {
    $this->withoutMiddleware(RestrictedDocsAccess::class);

    $response = $this->get('/docs/partner.json');

    $response->assertOk();

    expect($response->json('paths'))->toHaveCount(1)
        ->and($response->json('paths'))->toHaveKey('/partner/ping')
        ->and($response->json('info.title'))->toBe('EventHub Partner API');
});

test('the partner OpenAPI document inherits the same Problem Details correction as the default one', function () {
    $this->withoutMiddleware(RestrictedDocsAccess::class);

    $response = $this->get('/docs/partner.json');

    $schema = $response->json('components.responses.AuthenticationException.content.application/problem+json.schema');

    expect($schema['required'])->toEqual(['type', 'title', 'status', 'detail', 'code']);
});

test('the partner OpenAPI document describes the booking confirmed webhook', function () {
    $this->withoutMiddleware(RestrictedDocsAccess::class);

    $response = $this->get('/docs/partner.json');

    $response->assertOk();

    $operation = $response->json('webhooks.bookingConfirmed.post');

    expect($operation['parameters'][0]['name'])->toBe('X-EventHub-Signature')
        ->and($operation['parameters'][0]['required'])->toBeTrue();

    $schema = $operation['requestBody']['content']['application/json']['schema'];

    expect($schema['required'])->toEqual(['event', 'data'])
        ->and($schema['properties']['data']['required'])->toEqual([
            'booking_id', 'event_id', 'event_title',
            'participant_name', 'participant_email', 'seats', 'confirmed_at',
        ]);
});

test('the default OpenAPI document does not describe webhooks, only the partner one does', function () {
    $this->withoutMiddleware(RestrictedDocsAccess::class);

    $response = $this->get('/docs/api.json');

    $response->assertOk();

    expect($response->json())->not->toHaveKey('webhooks');
});
