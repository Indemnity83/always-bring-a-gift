<?php

namespace Database\Factories;

use App\Models\EventType;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
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
            'person_id' => Person::factory(),
            'event_type_id' => EventType::factory(),
            'recurrence' => 'none',
            'date' => fake()->dateTimeBetween('-1 year', '+1 year'),
            'target_value' => fake()->optional()->randomFloat(2, 10, 500),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the event is yearly recurring
     */
    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'recurrence' => 'yearly',
        ]);
    }

    /**
     * Indicate that the event is upcoming (in the next 30 days)
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('now', '+30 days'),
        ]);
    }
}
