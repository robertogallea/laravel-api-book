<?php

use App\Support\Payments\PaymentGatewayCredentials;

test('reads the configured key and secret', function () {
    config(['services.payment.key' => 'pk_test_123']);
    config(['services.payment.secret' => 'sk_test_456']);

    expect(PaymentGatewayCredentials::key())->toBe('pk_test_123');
    expect(PaymentGatewayCredentials::secret())->toBe('sk_test_456');
});

test('refuses to return a missing key', function () {
    config(['services.payment.key' => null]);

    PaymentGatewayCredentials::key();
})->throws(RuntimeException::class, 'services.payment.key is not configured.');

test('refuses to return a missing secret', function () {
    config(['services.payment.secret' => null]);

    PaymentGatewayCredentials::secret();
})->throws(RuntimeException::class, 'services.payment.secret is not configured.');
