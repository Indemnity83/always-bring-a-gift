<?php

namespace Database\Factories;

use App\Models\NotificationRateLimit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationRateLimit>
 */
class NotificationRateLimitFactory extends Factory
{
    protected $model = NotificationRateLimit::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'channel' => $this->faker->randomElement(['mail', 'slack', 'discord', 'push']),
            'action' => 'send_notification',
            'attempts' => $this->faker->numberBetween(0, 5),
            'last_attempt_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'reset_at' => $this->faker->dateTimeBetween('now', '+1 hour'),
            'is_blocked' => $this->faker->boolean(20), // 20% chance of being blocked
        ];
    }

    /**
     * Indicate that the rate limit is blocked.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blocked' => true,
            'attempts' => $this->faker->numberBetween(3, 10),
        ]);
    }

    /**
     * Indicate that the rate limit is not blocked.
     */
    public function unblocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blocked' => false,
            'attempts' => $this->faker->numberBetween(0, 2),
        ]);
    }

    /**
     * Indicate the channel for the rate limit.
     */
    public function forChannel(string $channel): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => $channel,
        ]);
    }

    /**
     * Indicate the action for the rate limit.
     */
    public function forAction(string $action): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => $action,
        ]);
    }
}
