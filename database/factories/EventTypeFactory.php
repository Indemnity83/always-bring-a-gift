<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventType>
 */
class EventTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'is_custom' => true,
        ];
    }

    /**
     * Indicate that the event type is predefined
     */
    public function predefined(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_custom' => false,
        ]);
    }
}
