<?php

namespace App\Providers;

use App\Domain\Booking\Contracts\BookingNotifier;
use App\Domain\Booking\Notifiers\LogBookingNotifier;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BookingNotifier::class, LogBookingNotifier::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Creating a booking writes data and consumes an event's capacity: a caller abusing
        // it is throttled much sooner than one merely browsing the catalog. The action already
        // requires auth:sanctum (BookingController::middleware()), so the authenticated user is
        // always resolved by the time this limiter runs.
        RateLimiter::for('bookings', fn (Request $request) => Limit::perMinute(5)->by($request->user()->id));

        // Browsing the event catalog stays open to anonymous callers, so it is keyed by IP
        // rather than by user, with a far more permissive threshold.
        RateLimiter::for('events', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }
}
