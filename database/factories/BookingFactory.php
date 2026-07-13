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
}
