<?php

namespace App\Providers;

use App\Domain\Booking\Contracts\BookingNotifier;
use App\Domain\Booking\Notifiers\LogBookingNotifier;
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
        //
    }
}
