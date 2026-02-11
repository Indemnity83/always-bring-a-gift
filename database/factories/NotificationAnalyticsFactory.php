<?php

namespace Database\Factories;

use App\Models\NotificationAnalytics;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationAnalytics>
 */
class NotificationAnalyticsFactory extends Factory
{
    protected $model = NotificationAnalytics::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $sentCount = $this->faker->numberBetween(50, 1000);
        $deliveredCount = $this->faker->numberBetween(40, $sentCount);
        $failedCount = $sentCount - $deliveredCount;
        $readCount = $this->faker->numberBetween(10, $deliveredCount);
        $clickCount = $this->faker->numberBetween(0, $readCount);

        return [
            'channel' => $this->faker->randomElement(['mail', 'slack', 'discord', 'push']),
            'notification_type' => $this->faker->randomElement(['event_reminder', 'gift_suggestion', 'system_alert', 'marketing']),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'sent_count' => $sentCount,
            'delivered_count' => $deliveredCount,
            'failed_count' => $failedCount,
            'read_count' => $readCount,
            'click_count' => $clickCount,
            'delivery_rate' => $sentCount > 0 ? round(($deliveredCount / $sentCount) * 100, 2) : 0.00,
            'open_rate' => $sentCount > 0 ? round(($readCount / $sentCount) * 100, 2) : 0.00,
            'click_rate' => $sentCount > 0 ? round(($clickCount / $sentCount) * 100, 2) : 0.00,
            'avg_delivery_time' => $this->faker->randomFloat(2, 0.5, 10.0),
        ];
    }

    /**
     * Indicate the channel for the analytics.
     */
    public function forChannel(string $channel): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => $channel,
        ]);
    }

    /**
     * Indicate the notification type for the analytics.
     */
    public function forType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => $type,
        ]);
    }

    /**
     * Set the date for the analytics.
     */
    public function forDate($date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date,
        ]);
    }

    /**
     * Create analytics with good performance metrics.
     */
    public function withGoodPerformance(): static
    {
        $sentCount = $this->faker->numberBetween(100, 1000);
        $deliveredCount = (int) ($sentCount * 0.98); // 98% delivery rate
        $failedCount = $sentCount - $deliveredCount;
        $readCount = (int) ($sentCount * 0.35); // 35% open rate
        $clickCount = (int) ($sentCount * 0.08); // 8% click rate

        return $this->state(fn (array $attributes) => [
            'sent_count' => $sentCount,
            'delivered_count' => $deliveredCount,
            'failed_count' => $failedCount,
            'read_count' => $readCount,
            'click_count' => $clickCount,
            'delivery_rate' => 98.00,
            'open_rate' => 35.00,
            'click_rate' => 8.00,
            'avg_delivery_time' => $this->faker->randomFloat(2, 0.1, 2.0),
        ]);
    }

    /**
     * Create analytics with poor performance metrics.
     */
    public function withPoorPerformance(): static
    {
        $sentCount = $this->faker->numberBetween(100, 1000);
        $deliveredCount = (int) ($sentCount * 0.75); // 75% delivery rate
        $failedCount = $sentCount - $deliveredCount;
        $readCount = (int) ($sentCount * 0.05); // 5% open rate
        $clickCount = (int) ($sentCount * 0.01); // 1% click rate

        return $this->state(fn (array $attributes) => [
            'sent_count' => $sentCount,
            'delivered_count' => $deliveredCount,
            'failed_count' => $failedCount,
            'read_count' => $readCount,
            'click_count' => $clickCount,
            'delivery_rate' => 75.00,
            'open_rate' => 5.00,
            'click_rate' => 1.00,
            'avg_delivery_time' => $this->faker->randomFloat(2, 5.0, 15.0),
        ]);
    }

    /**
     * Create analytics with average performance metrics.
     */
    public function withAveragePerformance(): static
    {
        $sentCount = $this->faker->numberBetween(100, 1000);
        $deliveredCount = (int) ($sentCount * 0.90); // 90% delivery rate
        $failedCount = $sentCount - $deliveredCount;
        $readCount = (int) ($sentCount * 0.20); // 20% open rate
        $clickCount = (int) ($sentCount * 0.03); // 3% click rate

        return $this->state(fn (array $attributes) => [
            'sent_count' => $sentCount,
            'delivered_count' => $deliveredCount,
            'failed_count' => $failedCount,
            'read_count' => $readCount,
            'click_count' => $clickCount,
            'delivery_rate' => 90.00,
            'open_rate' => 20.00,
            'click_rate' => 3.00,
            'avg_delivery_time' => $this->faker->randomFloat(2, 1.0, 5.0),
        ]);
    }

    /**
     * Create analytics for event reminders.
     */
    public function forEventReminders(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => 'event_reminder',
            'delivery_rate' => $this->faker->randomFloat(2, 85.0, 99.0),
            'open_rate' => $this->faker->randomFloat(2, 25.0, 50.0),
            'click_rate' => $this->faker->randomFloat(2, 5.0, 15.0),
        ]);
    }

    /**
     * Create analytics for gift suggestions.
     */
    public function forGiftSuggestions(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => 'gift_suggestion',
            'delivery_rate' => $this->faker->randomFloat(2, 80.0, 95.0),
            'open_rate' => $this->faker->randomFloat(2, 15.0, 35.0),
            'click_rate' => $this->faker->randomFloat(2, 3.0, 12.0),
        ]);
    }

    /**
     * Create analytics for system alerts.
     */
    public function forSystemAlerts(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => 'system_alert',
            'delivery_rate' => $this->faker->randomFloat(2, 90.0, 100.0),
            'open_rate' => $this->faker->randomFloat(2, 40.0, 80.0),
            'click_rate' => $this->faker->randomFloat(2, 10.0, 30.0),
        ]);
    }

    /**
     * Create analytics for marketing notifications.
     */
    public function forMarketing(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => 'marketing',
            'delivery_rate' => $this->faker->randomFloat(2, 70.0, 90.0),
            'open_rate' => $this->faker->randomFloat(2, 10.0, 25.0),
            'click_rate' => $this->faker->randomFloat(2, 1.0, 8.0),
        ]);
    }

    /**
     * Create analytics for mail channel.
     */
    public function forMail(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'mail',
            'delivery_rate' => $this->faker->randomFloat(2, 85.0, 98.0),
            'open_rate' => $this->faker->randomFloat(2, 15.0, 40.0),
            'click_rate' => $this->faker->randomFloat(2, 2.0, 10.0),
        ]);
    }

    /**
     * Create analytics for Slack channel.
     */
    public function forSlack(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'slack',
            'delivery_rate' => $this->faker->randomFloat(2, 95.0, 100.0),
            'open_rate' => $this->faker->randomFloat(2, 30.0, 70.0),
            'click_rate' => $this->faker->randomFloat(2, 5.0, 20.0),
        ]);
    }

    /**
     * Create analytics for Discord channel.
     */
    public function forDiscord(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'discord',
            'delivery_rate' => $this->faker->randomFloat(2, 90.0, 99.0),
            'open_rate' => $this->faker->randomFloat(2, 25.0, 60.0),
            'click_rate' => $this->faker->randomFloat(2, 3.0, 15.0),
        ]);
    }

    /**
     * Create analytics for push channel.
     */
    public function forPush(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'push',
            'delivery_rate' => $this->faker->randomFloat(2, 80.0, 95.0),
            'open_rate' => $this->faker->randomFloat(2, 20.0, 50.0),
            'click_rate' => $this->faker->randomFloat(2, 4.0, 12.0),
        ]);
    }

    /**
     * Create analytics for today.
     */
    public function forToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now()->toDateString(),
        ]);
    }

    /**
     * Create analytics for yesterday.
     */
    public function forYesterday(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now()->subDay()->toDateString(),
        ]);
    }

    /**
     * Create analytics for the last 7 days.
     */
    public function forLastWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween('-7 days', '-1 day'),
        ]);
    }

    /**
     * Create analytics for the last 30 days.
     */
    public function forLastMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}
