<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'participant_id' => User::factory(),
            'participant_name' => fake()->name(),
            'participant_email' => fake()->safeEmail(),
            'seats' => fake()->numberBetween(1, 4),
        ];
    }

    /**
     * Indicate that the booking was made without a platform account, the same
     * way CreateBookingCommand creates one from the command line.
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'participant_id' => null,
        ]);
    }
}
