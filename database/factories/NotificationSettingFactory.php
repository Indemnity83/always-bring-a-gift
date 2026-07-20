<?php

namespace Database\Factories;

use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationSetting>
 */
class NotificationSettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<NotificationSetting>
     */
    protected $model = NotificationSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'lead_time_days' => 7,
            'remind_at' => '09:00:00',
            'channels' => ['mail'],
            'slack_webhook_url' => null,
            'discord_webhook_url' => null,
            'push_endpoint' => null,
            'push_token' => null,
        ];
    }

    /**
     * Enable additional channels.
     */
    public function withChannels(array $channels): static
    {
        return $this->state(fn () => ['channels' => $channels]);
    }
}
