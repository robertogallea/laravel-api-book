<?php

namespace Tests;

use App\Domain\Booking\Contracts\BookingNotifier;
use App\Domain\Booking\Notifiers\LogBookingNotifier;
use App\Domain\Booking\Notifiers\WebhookBookingNotifier;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Most tests that create or cancel a booking do not care about actually reaching a
        // partner over HTTP: the harmless logging implementation from Chapter 2 keeps the rest
        // of the suite honest without faking an HTTP call every time a booking happens to be
        // created. Tests about webhook delivery itself call useRealWebhookNotifier() below, or
        // build the queued job directly.
        $this->app->bind(BookingNotifier::class, LogBookingNotifier::class);
    }

    protected function useRealWebhookNotifier(): void
    {
        $this->app->bind(BookingNotifier::class, WebhookBookingNotifier::class);
    }
}
