<?php

namespace App\Livewire\Settings;

use App\Models\NotificationSetting;
use App\Notifications\TestChannelNotification;
use App\Services\NotificationRateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;

class Notifications extends Component
{
    public int $leadTimeDays = 7;

    public string $remindAt = '09:00';

    /** @var list<string> */
    public array $channels = ['mail'];

    public ?string $slackWebhook = null;

    public ?string $discordWebhook = null;

    public ?string $pushEndpoint = null;

    public ?string $pushToken = null;

    public array $rateLimitStats = [];

    /**
     * Mount component with current settings.
     */
    public function mount(): void
    {
        $settings = NotificationSetting::forUser(Auth::user(), true);

        $this->leadTimeDays = $settings->lead_time_days;
        $this->remindAt = substr($settings->remind_at, 0, 5);
        $this->channels = $settings->resolved_channels;
        $this->slackWebhook = $settings->slackWebhook();
        $this->discordWebhook = $settings->discordWebhook();
        $this->pushEndpoint = $settings->pushEndpoint();
        $this->pushToken = $settings->pushToken();

        // Best effort: rate limit stats
        try {
            $this->rateLimitStats = app(NotificationRateLimiter::class)->getUserRateLimitStats(Auth::user());
        } catch (\Throwable $e) {
            // Do not block page load
        }
    }

    /**
     * Save settings.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'leadTimeDays' => ['required', 'integer', 'min:1', 'max:365'],
            'remindAt' => ['required', 'date_format:H:i'],
            'channels' => ['array'],
            'channels.*' => ['in:mail,slack,discord,push'],
            'slackWebhook' => ['nullable', 'url'],
            'discordWebhook' => ['nullable', 'url'],
            'pushEndpoint' => ['nullable', 'url'],
            'pushToken' => ['nullable', 'string', 'max:255'],
        ]);

        $enabledChannels = collect($validated['channels'] ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($enabledChannels)) {
            $enabledChannels = ['mail'];
        }

        $settings = NotificationSetting::forUser(Auth::user(), true);
        $settings->update([
            'lead_time_days' => $validated['leadTimeDays'],
            'remind_at' => $validated['remindAt'].':00',
            'channels' => $enabledChannels,
            'slack_webhook_url' => in_array('slack', $enabledChannels, true) ? $validated['slackWebhook'] : null,
            'discord_webhook_url' => in_array('discord', $enabledChannels, true) ? $validated['discordWebhook'] : null,
            'push_endpoint' => in_array('push', $enabledChannels, true) ? $validated['pushEndpoint'] : null,
            'push_token' => in_array('push', $enabledChannels, true) ? $validated['pushToken'] : null,
        ]);

        $this->channels = $enabledChannels;
        $this->dispatch('notifications-saved');
    }

    /**
     * Send a test notification for a given channel using current settings.
     */
    public function sendTest(string $channel): void
    {
        $targets = $this->channelTargets();

        if (! $targets->has($channel)) {
            $this->addError('test', __(':channel is not configured. Please add a webhook URL and check the channel checkbox.', ['channel' => ucfirst($channel)]));

            return;
        }

        $notification = new TestChannelNotification($channel, Auth::user()->name);
        $target = $targets->get($channel);

        // Log to notifications channel for debugging
        $sanitizedTarget = $this->sanitizeTarget($channel, $target);

        try {
            \Log::channel('notifications')->info('Sending test notification', [
                'user_id' => Auth::id(),
                'channel' => $channel,
                'target' => $sanitizedTarget,
            ]);

            $this->dispatchNotification(Auth::user(), $notification, $channel, $target);

            \Log::channel('notifications')->info('Test notification dispatched successfully', [
                'user_id' => Auth::id(),
                'channel' => $channel,
            ]);

            $this->dispatch('test-sent', channel: ucfirst($channel));
        } catch (\Throwable $e) {
            \Log::channel('notifications')->error('Test notification failed', [
                'user_id' => Auth::id(),
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);

            report($e);
            $this->addError('test', __('Unable to send :channel test: :error', [
                'channel' => ucfirst($channel),
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Sanitize target for logging (hide sensitive parts of webhook URLs).
     */
    protected function sanitizeTarget(string $channel, string|array|null $target): string
    {
        if (! $target) {
            return 'null';
        }

        if (is_array($target)) {
            $endpoint = $target['endpoint'] ?? 'unknown';

            return preg_replace('#(https?://[^/]+/)[^?]*#', '$1***', $endpoint) ?? $endpoint;
        }

        return preg_replace('#(https?://[^/]+/)[^?]*#', '$1***', $target) ?? $target;
    }

    /**
     * Determine which channels have enough configuration to send.
     *
     * @return \Illuminate\Support\Collection<string, string|array|null>
     */
    protected function channelTargets()
    {
        $email = Auth::user()?->email;

        return collect($this->channels)
            ->mapWithKeys(function (string $channel) use ($email) {
                return match ($channel) {
                    'mail' => $email ? ['mail' => $email] : [],
                    'slack' => $this->slackWebhook ? ['slack' => $this->slackWebhook] : [],
                    'discord' => $this->discordWebhook ? ['discord' => $this->discordWebhook] : [],
                    'push' => $this->pushEndpoint ? ['push' => [
                        'endpoint' => $this->pushEndpoint,
                        'token' => $this->pushToken,
                    ]] : [],
                    default => [],
                };
            })
            ->filter();
    }

    /**
     * Dispatch the notification to the correct transport.
     */
    protected function dispatchNotification(
        $user,
        TestChannelNotification $notification,
        string $channel,
        string|array|null $target
    ): void {
        if (! $target) {
            return;
        }

        switch ($channel) {
            case 'mail':
                $user->notify($notification);

                return;
            case 'slack':
                Notification::route('slack', $target)->notify($notification);

                return;
            case 'discord':
                Notification::route('discord', $target)->notify($notification);

                return;
            case 'push':
                Notification::route('push', $target)->notify($notification);

                return;
        }
    }

    public function render()
    {
        return view('livewire.settings.notifications');
    }
}
