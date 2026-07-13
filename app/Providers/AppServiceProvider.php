<?php

namespace App\Providers;

use App\Domain\Booking\Contracts\BookingNotifier;
use App\Domain\Booking\Notifiers\LogBookingNotifier;
use App\Support\OpenApi\ProblemDetailsResponses;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\Types\MixedType;
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

        // Scramble generates request/response schemas straight from the code it can see (Form
        // Requests, API Resources, route middleware), but it cannot see the Problem Details
        // envelope that bootstrap/app.php wraps around every error: registered once here, this
        // applies to every documented endpoint, not just the ones shown in this book.
        Scramble::afterOpenApiGenerated(new ProblemDetailsResponses);

        // A field Scramble cannot narrow down, one whose type it cannot resolve, is documented
        // as an unconstrained schema, {}: technically valid OpenAPI, but a silent gap in a
        // public contract that nobody would notice just by browsing the generated page. throw:
        // false means the live document keeps serving normally when this happens; the gap only
        // surfaces to `scramble:analyze`, the check meant to catch exactly this before release.
        // The two ignored path shapes are a known, harmless Scramble quirk, not a real gap:
        // a 204 No Content response has no body by definition, but Scramble still infers an
        // array type for it and cannot resolve what its (nonexistent) items would contain.
        Scramble::preventSchema(MixedType::class, ignorePaths: [
            '*/delete/responses/*',
            '*logout/post/responses/*',
        ], throw: false);
    }
}
