<?php

use App\Domain\Booking\Jobs\SendBookingConfirmedWebhook;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

test('it delivers the webhook to the configured partner url', function () {
    Http::fake([
        'partner.test/*' => Http::response(['received' => true], 200),
    ]);

    $event = Event::factory()->create(['title' => 'Laravel Conference']);
    $booking = Booking::factory()->create(['event_id' => $event->id]);

    (new SendBookingConfirmedWebhook($booking))->handle();

    Http::assertSent(function ($request) use ($booking) {
        return $request->url() === config('services.partner.webhook_url')
            && $request['event'] === 'booking.confirmed'
            && $request['data']['booking_id'] === $booking->id;
    });
});

test('it configures the job with the max attempts and backoff read from config', function () {
    $booking = Booking::factory()->create();

    $job = new SendBookingConfirmedWebhook($booking);

    expect($job->tries)->toBe(config('services.partner.webhook_max_attempts'))
        ->and($job->tries)->toBe(3)
        ->and($job->backoff())->toBe([10, 30]);
});

test('it fails the attempt when the partner responds with a server error', function () {
    Http::fake([
        'partner.test/*' => Http::response(null, 500),
    ]);

    $booking = Booking::factory()->create();

    (new SendBookingConfirmedWebhook($booking))->handle();
})->throws(RequestException::class);

test('it fails the attempt when the partner is unreachable', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection refused.');
    });

    $booking = Booking::factory()->create();

    (new SendBookingConfirmedWebhook($booking))->handle();
})->throws(ConnectionException::class);

test('it logs the exhaustion of every retry attempt as a delivery failure', function () {
    Log::spy();

    $booking = Booking::factory()->create();

    (new SendBookingConfirmedWebhook($booking))->failed(new ConnectionException('Connection refused.'));

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn ($message) => str_contains($message, "Webhook for booking #{$booking->id}")
            && str_contains($message, '3 attempt'));
});
