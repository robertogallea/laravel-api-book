<?php

use App\Support\Webhooks\WebhookSigner;

test('it signs a body with the configured shared secret', function () {
    expect(WebhookSigner::sign('payload'))
        ->toBe(hash_hmac('sha256', 'payload', config('services.partner.webhook_secret')));
});

test('it refuses to sign when no shared secret is configured', function () {
    config(['services.partner.webhook_secret' => null]);

    WebhookSigner::sign('payload');
})->throws(RuntimeException::class, 'services.partner.webhook_secret is not configured.');
