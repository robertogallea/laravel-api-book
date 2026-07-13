<?php

namespace App\Domain\Booking\Jobs;

use App\Domain\Booking\DataTransferObjects\BookingConfirmedNotificationPayload;
use App\Models\Booking;
use App\Support\Webhooks\WebhookSigner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendBookingConfirmedWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // How many times the queue worker attempts this job, in total, before giving up. Configured
    // explicitly (services.partner.webhook_max_attempts) instead of a literal here, so the limit
    // can be tuned per environment without touching this class.
    public int $tries;

    public function __construct(
        public readonly Booking $booking,
    ) {
        $this->tries = config('services.partner.webhook_max_attempts');
    }

    // One wait per retry, not per attempt: with $tries = 3, only two waits separate the three
    // attempts. Laravel repeats the last value for any attempt beyond the array's length.
    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(): void
    {
        $payload = BookingConfirmedNotificationPayload::fromBooking($this->booking)->toArray();

        // Signed over the exact bytes leaving this job, not over the array: withBody() sends
        // this string as-is, so the partner hashes precisely what it received, not a JSON
        // re-encoding of it that could legitimately differ (key order, escaping) from ours.
        $body = json_encode($payload);

        // A non-2xx response, or the partner being unreachable altogether, both surface here as
        // an exception: the queue worker interprets it as a failed attempt and applies the
        // backoff policy above, exactly like any other failed job.
        Http::withBody($body, 'application/json')
            ->withHeaders(WebhookSigner::header($body))
            ->post(config('services.partner.webhook_url'))
            ->throw();
    }

    public function failed(Throwable $exception): void
    {
        Log::error(
            "Webhook for booking #{$this->booking->id} was not delivered after {$this->tries} attempt(s).",
            ['exception' => $exception->getMessage()],
        );
    }
}
