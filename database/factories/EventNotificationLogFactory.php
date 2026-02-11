<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventNotificationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventNotificationLog>
 */
class EventNotificationLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<EventNotificationLog>
     */
    protected $model = EventNotificationLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'channel' => 'mail',
            'remind_for_date' => now()->toDateString(),
            'sent_at' => now(),
        ];
    }
}
