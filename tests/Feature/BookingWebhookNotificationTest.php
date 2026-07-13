<?php

use App\Domain\Booking\Jobs\SendBookingCancelledWebhook;
use App\Domain\Booking\Jobs\SendBookingConfirmedWebhook;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

// TestCase binds the harmless LogBookingNotifier by default (see Tests\TestCase::setUp()):
// these tests are specifically about the queueing behavior of WebhookBookingNotifier, so they
// opt back into it explicitly instead of relying on whatever the default happens to be.
beforeEach(fn () => $this->useRealWebhookNotifier());

test('it queues a webhook job when a booking is created', function () {
    Queue::fake();

    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 5]);

    $payload = [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 2,
    ];

    $this->post("/api/v1/events/{$event->id}/bookings", $payload)->assertCreated();

    Queue::assertPushed(SendBookingConfirmedWebhook::class, function ($job) use ($event) {
        return $job->booking->event_id === $event->id;
    });
});

test('creating a booking never reaches the real partner, and prepares a correctly signed confirmation', function () {
    // No Queue::fake() here, on purpose: under the sync connection this suite runs on
    // (phpunit.xml), SendBookingConfirmedWebhook::handle() executes for real, in process, as
    // part of this same request. Http::fake() is what keeps that real execution from ever
    // reaching an actual partner over the network.
    Http::fake([
        'partner.test/*' => Http::response(['received' => true], 200),
    ]);

    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['capacity' => 5, 'title' => 'Laravel Conference']);

    $payload = [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 2,
    ];

    $bookingId = $this->post("/api/v1/events/{$event->id}/bookings", $payload)
        ->assertCreated()
        ->json('data.id');

    // Isolating the dependency only proves nothing real was called. Asserting on the request
    // this specifically sent, event type, booking, event title, and a signature recomputed
    // independently over the exact body, proves the notification was prepared correctly, not
    // just that some request happened to leave the job.
    Http::assertSent(function ($request) use ($bookingId, $event) {
        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->body(), config('services.partner.webhook_secret'));

        return $request->url() === config('services.partner.webhook_url')
            && $request['event'] === 'booking.confirmed'
            && $request['data']['booking_id'] === $bookingId
            && $request['data']['event_title'] === $event->title
            && $request->hasHeader('X-EventHub-Signature', $expectedSignature);
    });
});

test('it queues a webhook job when a booking is cancelled through v2', function () {
    Queue::fake();

    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create(['starts_at' => now()->addWeek()]);
    $booking = Booking::factory()->create(['event_id' => $event->id, 'participant_id' => $participant->id]);

    $this->delete("/api/v2/bookings/{$booking->id}")->assertNoContent();

    Queue::assertPushed(SendBookingCancelledWebhook::class, function ($job) use ($booking, $event) {
        return $job->bookingId === $booking->id && $job->eventId === $event->id;
    });
});

test('cancelling a booking through v1 does not queue a webhook job, unlike v2', function () {
    Queue::fake();

    $participant = User::factory()->create();
    Sanctum::actingAs($participant);
    $event = Event::factory()->create(['starts_at' => now()->addWeek()]);
    $booking = Booking::factory()->create(['event_id' => $event->id, 'participant_id' => $participant->id]);

    $this->delete("/api/v1/bookings/{$booking->id}")->assertNoContent();

    Queue::assertNotPushed(SendBookingCancelledWebhook::class);
});
