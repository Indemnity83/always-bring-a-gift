<?php

namespace Database\Factories;

use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationTemplate>
 */
class NotificationTemplateFactory extends Factory
{
    protected $model = NotificationTemplate::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'notification_type' => $this->faker->randomElement(['event_reminder', 'gift_suggestion', 'system_alert', 'marketing']),
            'channel' => $this->faker->randomElement(['mail', 'slack', 'discord', 'push']),
            'subject' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'variables' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'Recipient name'],
                ['name' => 'event_name', 'type' => 'string', 'required' => true, 'description' => 'Event name'],
                ['name' => 'date', 'type' => 'date', 'required' => false, 'description' => 'Event date'],
            ],
            'is_active' => true,
            'is_system' => false,
        ];
    }

    /**
     * Indicate that the template is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the template is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the template is a system template.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
            'user_id' => null,
        ]);
    }

    /**
     * Indicate that the template is user-created.
     */
    public function userCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => false,
            'user_id' => User::factory(),
        ]);
    }

    /**
     * Indicate the template type.
     */
    public function forType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => $type,
        ]);
    }

    /**
     * Indicate the template channel.
     */
    public function forChannel(string $channel): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => $channel,
        ]);
    }

    /**
     * Set the template creator.
     */
    public function createdBy(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Set specific variables for the template.
     */
    public function withVariables(array $variables): static
    {
        return $this->state(fn (array $attributes) => [
            'variables' => $variables,
        ]);
    }

    /**
     * Create an event reminder template.
     */
    public function forEventReminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Event Reminder',
            'notification_type' => 'event_reminder',
            'subject' => 'Reminder: {{event_name}} is coming up!',
            'content' => "Hello {{name}},\n\nThis is a reminder that {{event_name}} is scheduled for {{date}}.\n\nDon't forget to prepare!\n\nBest regards",
            'variables' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'Recipient name'],
                ['name' => 'event_name', 'type' => 'string', 'required' => true, 'description' => 'Event name'],
                ['name' => 'date', 'type' => 'date', 'required' => true, 'description' => 'Event date'],
            ],
        ]);
    }

    /**
     * Create a gift suggestion template.
     */
    public function forGiftSuggestion(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Gift Suggestion',
            'notification_type' => 'gift_suggestion',
            'subject' => 'Gift suggestion for {{event_name}}',
            'content' => "Hi {{name}},\n\nHere's a gift suggestion for {{event_name}}:\n\n{{gift_idea}}\n\nHope this helps!\n\nBest regards",
            'variables' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'Recipient name'],
                ['name' => 'event_name', 'type' => 'string', 'required' => true, 'description' => 'Event name'],
                ['name' => 'gift_idea', 'type' => 'string', 'required' => true, 'description' => 'Gift idea'],
            ],
        ]);
    }

    /**
     * Create a system alert template.
     */
    public function forSystemAlert(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'System Alert',
            'notification_type' => 'system_alert',
            'subject' => 'System Alert: {{alert_type}}',
            'content' => "Attention {{name}},\n\n{{alert_message}}\n\nPlease take appropriate action.\n\nSystem Administrator",
            'variables' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'Recipient name'],
                ['name' => 'alert_type', 'type' => 'string', 'required' => true, 'description' => 'Alert type'],
                ['name' => 'alert_message', 'type' => 'string', 'required' => true, 'description' => 'Alert message'],
            ],
        ]);
    }

    /**
     * Create a marketing template.
     */
    public function forMarketing(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Marketing Notification',
            'notification_type' => 'marketing',
            'subject' => 'Special offer for {{name}}!',
            'content' => "Hello {{name}},\n\nWe have a special offer just for you!\n\n{{offer_details}}\n\nDon't miss out!\n\nBest regards",
            'variables' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'Recipient name'],
                ['name' => 'offer_details', 'type' => 'string', 'required' => true, 'description' => 'Offer details'],
            ],
        ]);
    }

    /**
     * Create a mail template.
     */
    public function forMail(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'mail',
            'subject' => $this->faker->sentence(),
        ]);
    }

    /**
     * Create a Slack template.
     */
    public function forSlack(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'slack',
            'subject' => null,
            'content' => $this->faker->sentence().' {{name}}',
        ]);
    }

    /**
     * Create a Discord template.
     */
    public function forDiscord(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'discord',
            'subject' => null,
            'content' => $this->faker->sentence().' {{name}}',
        ]);
    }

    /**
     * Create a push notification template.
     */
    public function forPush(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'push',
            'subject' => $this->faker->words(3, true),
            'content' => $this->faker->sentence(),
        ]);
    }
}
