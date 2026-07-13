<?php

namespace App\Support\Webhooks;

use RuntimeException;

class WebhookSigner
{
    // Every outgoing notification shares one secret with the partner: EventHub has only one
    // partner integration today (Chapter 4), so a single shared secret, not one per event type
    // or per partner, is the simplest thing that matches the actual number of consumers.
    public static function sign(string $body): string
    {
        $secret = config('services.partner.webhook_secret');

        // An unset secret would otherwise reach hash_hmac() as null (or an empty string): every
        // notification would still get signed, just with a key any attacker can guess on the
        // first try. Failing here, where the misconfiguration actually is, is safer than a
        // signature that verifies as valid without ever having protected anything.
        if (! $secret) {
            throw new RuntimeException('services.partner.webhook_secret is not configured.');
        }

        return hash_hmac('sha256', $body, $secret);
    }

    public static function header(string $body): array
    {
        return ['X-EventHub-Signature' => 'sha256='.self::sign($body)];
    }
}
