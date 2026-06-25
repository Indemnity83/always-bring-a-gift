<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'notification_type' => $this->faker->randomElement(['event_reminder', 'gift_suggestion', 'system_alert', 'marketing']),
            'enabled' => true,
            'channels' => $this->faker->randomElements(['mail', 'slack', 'discord', 'push'], $this->faker->numberBetween(1, 3)),
            'lead_time_minutes' => $this->faker->numberBetween(15, 1440), // 15 minutes to 24 hours
            'quiet_hours_start' => $this->faker->optional()->dateTimeBetween('22:00', '23:00'),
            'quiet_hours_end' => $this->faker->optional()->dateTimeBetween('06:00', '08:00'),
            'respect_quiet_hours' => $this->faker->boolean(70), // 70% chance of respecting quiet hours
        ];
    }

    /**
     * Indicate that the preference is enabled.
     */
    public function enabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => true,
        ]);
    }

    /**
     * Indicate that the preference is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }

    /**
     * Indicate the notification type for the preference.
     */
    public function forType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => $type,
        ]);
    }

    /**
     * Indicate the user for the preference.
     */
    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Set specific channels for the preference.
     */
    public function withChannels(array $channels): static
    {
        return $this->state(fn (array $attributes) => [
            'channels' => $channels,
        ]);
    }

    /**
     * Set the lead time in minutes.
     */
    public function withLeadTime(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'lead_time_minutes' => $minutes,
        ]);
    }

    /**
     * Enable quiet hours with specific times.
     */
    public function withQuietHours(string $startTime = '22:00', string $endTime = '08:00'): static
    {
        return $this->state(fn (array $attributes) => [
            'quiet_hours_start' => now()->setTimeFromTimeString($startTime),
            'quiet_hours_end' => now()->setTimeFromTimeString($endTime),
            'respect_quiet_hours' => true,
        ]);
    }

    /**
     * Disable quiet hours.
     */
    public function withoutQuietHours(): static
    {
        return $this->state(fn (array $attributes) => [
            'quiet_hours_start' => null,
            'quiet_hours_end' => null,
            'respect_quiet_hours' => false,
        ]);
    }

    /**
     * Create a preference for event reminders.
     */
    public function forEventReminders(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => 'event_reminder',
            'lead_time_minutes' => $this->faker->numberBetween(60, 1440), // 1 hour to 24 hours
        ]);
    }

    /**
     * Create a preference for gift suggestions.
     */
    public function forGiftSuggestions(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => 'gift_suggestion',
            'lead_time_minutes' => $this->faker->numberBetween(10080, 43200), // 1 week to 1 month
        ]);
    }

    /**
     * Create a preference for system alerts.
     */
    public function forSystemAlerts(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => 'system_alert',
            'lead_time_minutes' => 0, // Immediate
        ]);
    }

    /**
     * Create a preference for marketing notifications.
     */
    public function forMarketing(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => 'marketing',
            'lead_time_minutes' => $this->faker->numberBetween(1440, 10080), // 1 day to 1 week
        ]);
    }
}
