<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Notifications\BookingConfirmed;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

test('creating a booking queues a confirmation notification instead of sending it inline', function () {
    Queue::fake();

    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->create(['title' => 'Laravel Meetup Palermo', 'capacity' => 5]);

    $this->postJson("/api/v1/events/{$event->id}/bookings", [
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 2,
    ])->assertCreated();

    // SendQueuedNotifications, the job every queued Notification is wrapped in: what this
    // asserts is that BookingConfirmed never runs inline as part of this request, only that a
    // job describing it lands on the queue, addressed to the booking's own participant_email,
    // not to whichever registered User happens to be authenticated.
    Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job) {
        return $job->notification instanceof BookingConfirmed
            && $job->notification->booking->participant_email === 'ada@example.com'
            && $job->notifiables->first()->routeNotificationFor('mail') === 'ada@example.com';
    });
});

test('the queued confirmation notification renders the booking details it was built from', function () {
    $event = Event::factory()->create(['title' => 'Laravel Meetup Palermo']);
    $booking = Booking::factory()->create([
        'event_id' => $event->id,
        'participant_name' => 'Ada Lovelace',
        'participant_email' => 'ada@example.com',
        'seats' => 2,
    ]);

    $mail = (new BookingConfirmed($booking))->toMail(new stdClass);

    expect($mail->subject)->toBe('Booking confirmed: Laravel Meetup Palermo');
    expect($mail->greeting)->toBe('Hi Ada Lovelace,');
    expect($mail->introLines)->toContain('Your booking for "Laravel Meetup Palermo" is confirmed.');
    expect($mail->introLines)->toContain('Seats booked: 2');
});
