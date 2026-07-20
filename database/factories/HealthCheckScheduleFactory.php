<?php

namespace Database\Factories;

use App\Models\HealthCheckSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HealthCheckSchedule>
 */
class HealthCheckScheduleFactory extends Factory
{
    protected $model = HealthCheckSchedule::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'channel' => $this->faker->randomElement(['mail', 'slack', 'discord', 'push']),
            'check_type' => $this->faker->randomElement(['connectivity', 'authentication', 'rate_limit', 'delivery']),
            'status' => $this->faker->randomElement(['healthy', 'warning', 'critical']),
            'details' => [
                'message' => $this->faker->sentence(),
                'code' => $this->faker->randomNumber(3),
                'provider' => $this->faker->word(),
            ],
            'response_time_ms' => $this->faker->numberBetween(50, 2000),
            'checked_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    /**
     * Indicate that the health check is healthy.
     */
    public function healthy(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'healthy',
            'response_time_ms' => $this->faker->numberBetween(50, 500),
            'details' => [
                'message' => 'All systems operational',
                'code' => 200,
                'provider' => $this->faker->word(),
            ],
        ]);
    }

    /**
     * Indicate that the health check has a warning.
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'warning',
            'response_time_ms' => $this->faker->numberBetween(500, 1500),
            'details' => [
                'message' => 'Degraded performance detected',
                'code' => $this->faker->numberBetween(400, 499),
                'provider' => $this->faker->word(),
            ],
        ]);
    }

    /**
     * Indicate that the health check is critical.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'critical',
            'response_time_ms' => $this->faker->numberBetween(1500, 5000),
            'details' => [
                'message' => 'Service unavailable',
                'code' => $this->faker->numberBetween(500, 599),
                'provider' => $this->faker->word(),
            ],
        ]);
    }

    /**
     * Indicate the channel for the health check.
     */
    public function forChannel(string $channel): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => $channel,
        ]);
    }

    /**
     * Indicate the check type for the health check.
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'check_type' => $type,
        ]);
    }

    /**
     * Indicate that the health check is recent (within the last hour).
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the health check is old (more than 24 hours ago).
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_at' => $this->faker->dateTimeBetween('-2 days', '-1 day'),
        ]);
    }
}
