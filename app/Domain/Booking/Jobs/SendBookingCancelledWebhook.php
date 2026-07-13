<?php

namespace App\Domain\Booking\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBookingCancelledWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // The booking row is already gone by the time this job runs: it carries the plain
    // identifiers it needs instead of the Booking model, so a worker picking this job up
    // later never tries to re-fetch a row that no longer exists.
    public function __construct(
        public readonly int $bookingId,
        public readonly int $eventId,
    ) {}

    public function handle(): void
    {
        Log::info("Webhook for booking #{$this->bookingId} (event #{$this->eventId}) queued for delivery after cancellation.");
    }
}
