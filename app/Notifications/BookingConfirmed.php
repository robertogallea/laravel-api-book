<?php

namespace App\Notifications;

use App\Domain\Booking\DataTransferObjects\BookingConfirmedNotificationPayload;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class BookingConfirmed extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Booking $booking,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Built here, not in the constructor: SerializesModels stores only the booking's key
        // in the queue payload, then re-fetches a fresh row when the worker actually runs this
        // job, so the request that dispatches this notification never has to read event.title
        // (BookingConfirmedNotificationPayload::fromBooking()) just to construct it.
        $payload = BookingConfirmedNotificationPayload::fromBooking($this->booking);

        return (new MailMessage)
            ->subject("Booking confirmed: {$payload->eventTitle}")
            ->greeting("Hi {$payload->participantName},")
            ->line("Your booking for \"{$payload->eventTitle}\" is confirmed.")
            ->line("Seats booked: {$payload->seats}")
            ->line('Keep this email as confirmation of your booking.');
    }
}
