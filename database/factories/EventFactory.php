<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organizer_id' => User::factory()->organizer(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'starts_at' => fake()->dateTimeBetween('+1 week', '+6 months'),
            'capacity' => fake()->numberBetween(10, 200),
        ];
    }

    /**
     * Indicate that the event has no seats left, with a booking that actually
     * accounts for the full capacity, not just the flag set on its own.
     */
    public function soldOut(): static
    {
        return $this->afterCreating(function (Event $event) {
            Booking::factory()->create([
                'event_id' => $event->id,
                'seats' => $event->capacity,
            ]);

            $event->update(['sold_out_at' => now()]);
        });
    }
}
