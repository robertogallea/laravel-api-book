<?php

namespace App\Support\Payments;

use RuntimeException;

class PaymentGatewayCredentials
{
    public static function key(): string
    {
        return self::required('key');
    }

    public static function secret(): string
    {
        return self::required('secret');
    }

    // Same principle as WebhookSigner::sign() (Chapter 6): a missing credential must stop the
    // request here, not travel on as null and reach the payment gateway as an empty string,
    // where it would fail with a confusing error far from its actual cause.
    private static function required(string $name): string
    {
        $value = config("services.payment.{$name}");

        if (! $value) {
            throw new RuntimeException("services.payment.{$name} is not configured.");
        }

        return $value;
    }
}
