<?php

namespace App\Notifications;

use App\Notifications\Channels\DiscordWebhookChannel;
use App\Notifications\Channels\PushWebhookChannel;
use App\Notifications\Channels\SlackWebhookChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TestChannelNotification extends Notification
{
    public function __construct(
        protected string $channel,
        protected string $name
    ) {}

    public function via(object $notifiable): array
    {
        return match ($this->channel) {
            'mail' => ['mail'],
            'slack' => [SlackWebhookChannel::class],
            'discord' => [DiscordWebhookChannel::class],
            'push' => [PushWebhookChannel::class],
            default => [],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Test notification')
            ->greeting('Hi '.$this->name)
            ->line('This is a test notification to confirm your reminder channel is configured.');
    }

    /**
     * Slack webhook payload.
     *
     * @return array<string, mixed>
     */
    public function toSlack(object $notifiable): array
    {
        return [
            'text' => 'Test reminder notification from ABAG for '.$this->name,
        ];
    }

    public function toDiscord(object $notifiable): array
    {
        return [
            'content' => 'Test reminder notification from ABAG for '.$this->name,
        ];
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => 'Test reminder notification',
            'body' => 'Push channel is working for '.$this->name,
        ];
    }
}
