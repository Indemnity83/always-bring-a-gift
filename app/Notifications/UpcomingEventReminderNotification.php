<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;
use App\Notifications\Channels\DiscordWebhookChannel;
use App\Notifications\Channels\PushWebhookChannel;
use App\Notifications\Channels\SlackWebhookChannel;
use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpcomingEventReminderNotification extends Notification
{
    public function __construct(
        protected Event $event,
        protected Carbon $occursOn,
        protected User $user,
        protected string $channel,
        protected int $daysAway
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
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

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Upcoming event reminder')
            ->greeting('Hi '.$this->user->name.'!')
            ->line($this->headline())
            ->line($this->body())
            ->line('Set aside a moment to confirm your gift or mark it complete once it is handled.');
    }

    /**
     * Build the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): array
    {
        return [
            'text' => $this->headline(),
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => '*'.$this->event->display_name.'* for '.$this->event->person->name,
                    ],
                    'fields' => [
                        [
                            'type' => 'mrkdwn',
                            'text' => "*When:*\n".$this->occursOn->toFormattedDateString(),
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Status:*\n".ucfirst($this->urgencyLabel()),
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Days Away:*\n".$this->daysAway,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build the Discord webhook payload.
     *
     * @return array<string, mixed>
     */
    public function toDiscord(object $notifiable): array
    {
        return [
            'content' => $this->headline(),
            'embeds' => [
                [
                    'title' => $this->event->display_name.' for '.$this->event->person->name,
                    'description' => $this->body(),
                    'timestamp' => $this->occursOn->toIso8601String(),
                    'color' => 0xF97316,
                ],
            ],
        ];
    }

    /**
     * Build the Push webhook payload.
     *
     * @return array<string, mixed>
     */
    public function toPush(object $notifiable): array
    {
        return [
            'title' => $this->headline(),
            'body' => $this->body(),
            'event_id' => $this->event->id,
            'occurs_on' => $this->occursOn->toDateString(),
            'person' => $this->event->person->name,
            'event_type' => $this->event->display_name,
            'urgency' => $this->urgencyLabel(),
        ];
    }

    /**
     * The main line for the reminder.
     */
    protected function headline(): string
    {
        return sprintf(
            '%s for %s is %s',
            $this->event->display_name,
            $this->event->person->name,
            $this->timeDescriptor()
        );
    }

    /**
     * Supporting body copy for the reminder.
     */
    protected function body(): string
    {
        return sprintf(
            'Happening on %s. Budget: %s',
            $this->occursOn->toFormattedDateString(),
            $this->event->budget ? '$'.number_format((float) $this->event->budget, 2) : 'not set'
        );
    }

    /**
     * Describe how soon the event is.
     */
    protected function timeDescriptor(): string
    {
        return match (true) {
            $this->daysAway === 0 => 'today',
            $this->daysAway === 1 => 'tomorrow',
            default => "in {$this->daysAway} days",
        };
    }

    /**
     * Provide a short label for urgency.
     */
    protected function urgencyLabel(): string
    {
        return $this->daysAway <= 1 ? 'soon' : 'upcoming';
    }

    /**
     * Expose the channel used (primarily for tests and logging).
     */
    public function channelName(): string
    {
        return $this->channel;
    }

    /**
     * Expose the related event.
     */
    public function event(): Event
    {
        return $this->event;
    }
}
