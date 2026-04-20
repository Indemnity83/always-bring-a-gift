<?php

namespace Database\Factories;

use App\Models\NotificationRateLimitConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationRateLimitConfig>
 */
class NotificationRateLimitConfigFactory extends Factory
{
    protected $model = NotificationRateLimitConfig::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'channel' => $this->faker->randomElement(['mail', 'slack', 'discord', 'push']),
            'action' => 'send_notification',
            'max_attempts' => $this->faker->numberBetween(3, 10),
            'window_minutes' => $this->faker->numberBetween(60, 1440), // 1 hour to 24 hours
            'block_duration_minutes' => $this->faker->numberBetween(30, 480), // 30 minutes to 8 hours
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the configuration is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate the channel for the configuration.
     */
    public function forChannel(string $channel): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => $channel,
        ]);
    }

    /**
     * Indicate the action for the configuration.
     */
    public function forAction(string $action): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => $action,
        ]);
    }

    /**
     * Set specific rate limit parameters.
     */
    public function withLimits(int $maxAttempts, int $windowMinutes, int $blockDurationMinutes = 60): static
    {
        return $this->state(fn (array $attributes) => [
            'max_attempts' => $maxAttempts,
            'window_minutes' => $windowMinutes,
            'block_duration_minutes' => $blockDurationMinutes,
        ]);
    }
}
