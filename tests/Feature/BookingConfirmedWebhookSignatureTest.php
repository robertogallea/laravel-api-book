<?php

use App\Domain\Booking\Jobs\SendBookingConfirmedWebhook;
use App\Models\Booking;
use Illuminate\Support\Facades\Http;

test('the receiving partner can verify the signature with the shared secret', function () {
    Http::fake(['partner.test/*' => Http::response(['received' => true], 200)]);

    $booking = Booking::factory()->create();

    (new SendBookingConfirmedWebhook($booking))->handle();

    Http::assertSent(function ($request) {
        $signatureHeader = $request->header('X-EventHub-Signature')[0];
        [$algorithm, $signature] = explode('=', $signatureHeader, 2);

        // Standing in for the partner's own endpoint: it never sees $payload as a PHP array,
        // only the raw bytes of $request->body() and this header, exactly like a real receiver.
        $expected = hash_hmac('sha256', $request->body(), config('services.partner.webhook_secret'));

        return $algorithm === 'sha256' && hash_equals($expected, $signature);
    });
});

test('a signature computed with the wrong secret does not verify', function () {
    Http::fake(['partner.test/*' => Http::response(['received' => true], 200)]);

    $booking = Booking::factory()->create();

    (new SendBookingConfirmedWebhook($booking))->handle();

    Http::assertSent(function ($request) {
        $signatureHeader = $request->header('X-EventHub-Signature')[0];
        [, $signature] = explode('=', $signatureHeader, 2);

        $wrongSecret = hash_hmac('sha256', $request->body(), 'not-the-real-shared-secret');

        return ! hash_equals($wrongSecret, $signature);
    });
});

test('a tampered body no longer matches the original signature', function () {
    Http::fake(['partner.test/*' => Http::response(['received' => true], 200)]);

    $booking = Booking::factory()->create();

    (new SendBookingConfirmedWebhook($booking))->handle();

    Http::assertSent(function ($request) {
        $signatureHeader = $request->header('X-EventHub-Signature')[0];
        [, $signature] = explode('=', $signatureHeader, 2);

        $tamperedBody = $request->body().' ';
        $expectedForTamperedBody = hash_hmac('sha256', $tamperedBody, config('services.partner.webhook_secret'));

        return ! hash_equals($expectedForTamperedBody, $signature);
    });
});
