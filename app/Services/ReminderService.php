<?php

namespace App\Services;

use App\Jobs\SendNotificationJob;
use App\Models\Event;
use App\Models\EventNotificationLog;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Notifications\UpcomingEventReminderNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ReminderService
{
    protected NotificationRateLimiter $rateLimiter;

    public function __construct(NotificationRateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Send reminders for upcoming events.
     *
     * @param  int|null  $overrideDays  Optional override for how many days ahead to check.
     */
    public function sendUpcomingReminders(?int $overrideDays = null): int
    {
        $sentCount = 0;

        try {
            // Use chunked processing for memory efficiency
            User::with('notificationSetting')->chunk(100, function (EloquentCollection $users) use (
                $overrideDays,
                &$sentCount
            ): void {
                foreach ($users as $user) {
                    $settings = NotificationSetting::forUser($user);
                    if (! $settings) {
                        continue;
                    }

                    // Respect per-user reminder time; skip until the time has passed.
                    if (! $this->shouldSendNow($settings)) {
                        continue;
                    }

                    $channels = $this->channelTargets($settings);
                    if ($channels->isEmpty()) {
                        continue;
                    }

                    // Process events for this user using database cursor
                    $userSentCount = $this->processUserReminders($user, $settings, $channels, $overrideDays);
                    $sentCount += $userSentCount;
                }
            });

            Log::info('Reminder processing completed', [
                'total_sent' => $sentCount,
                'processed_at' => now()->toDateTimeString(),
            ]);

        } catch (\Throwable $exception) {
            Log::error('Failed to send upcoming reminders', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }

        return $sentCount;
    }

    /**
     * Determine if reminders should send for a user based on their configured time and timezone.
     */
    protected function shouldSendNow(NotificationSetting $settings): bool
    {
        $remindAt = $settings->remind_at ?? config('reminders.send_time', '09:00');
        $userTimezone = $settings->user?->getUserTimezone() ?? 'UTC';

        // Get the current time in the user's timezone
        $now = now($userTimezone);

        // Create the target time in the user's timezone
        $target = Carbon::createFromTimeString($remindAt, $userTimezone)->setDate(
            $now->year,
            $now->month,
            $now->day
        );

        return $now->greaterThanOrEqualTo($target);
    }

    /**
     * Process reminders for a single user.
     */
    protected function processUserReminders(
        User $user,
        NotificationSetting $settings,
        Collection $channels,
        ?int $overrideDays
    ): int {
        $sentCount = 0;
        $daysAhead = $overrideDays ?? $settings->lead_time_days ?? config('reminders.lead_time_days');
        $today = now()->startOfDay();
        $endDate = now()->addDays($daysAhead);

        // Use database cursor for memory efficiency
        Event::with(['person', 'eventType', 'completions'])
            ->whereHas('person', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->chunk(50, function (EloquentCollection $events) use (
                $user,
                $channels,
                $today,
                $endDate,
                &$sentCount
            ): void {
                foreach ($events as $event) {
                    $nextOccurrence = $event->next_occurrence;

                    if (! $nextOccurrence->between($today, $endDate, true)) {
                        continue;
                    }

                    // Skip reminders if the event is already marked complete for that year
                    if ($event->isCompletedForYear($nextOccurrence->year)) {
                        continue;
                    }

                    $daysAway = $today->diffInDays($nextOccurrence);

                    foreach ($channels as $channel => $target) {
                        if ($this->alreadySent($user->id, $event->id, $channel, $nextOccurrence)) {
                            continue;
                        }

                        // Check rate limits before dispatching
                        if (! $this->rateLimiter->canSendNotification($user, $channel)) {
                            Log::info('Notification skipped due to rate limit', [
                                'user_id' => $user->id,
                                'channel' => $channel,
                                'event_id' => $event->id,
                            ]);

                            continue;
                        }

                        $this->dispatchQueuedNotification($user, $event, $channel, $target, $nextOccurrence, $daysAway);
                        $sentCount++;
                    }
                }
            });

        return $sentCount;
    }

    /**
     * Get rate limit statistics for a user.
     */
    public function getUserRateLimitStats(User $user): array
    {
        return $this->rateLimiter->getUserRateLimitStats($user);
    }

    /**
     * Get users who have exceeded their rate limits.
     */
    public function getRateLimitedUsers(): Collection
    {
        return $this->rateLimiter->getRateLimitedUsers();
    }

    /**
     * Clean up expired rate limit entries.
     */
    public function cleanupExpiredRateLimitEntries(): int
    {
        return $this->rateLimiter->cleanupExpiredEntries();
    }

    /**
     * Determine which channels have enough configuration to send.
     *
     * @return Collection<string, string|array|null>
     */
    protected function channelTargets(NotificationSetting $settings): Collection
    {
        return collect($settings->resolved_channels)
            ->mapWithKeys(function (string $channel) use ($settings) {
                return match ($channel) {
                    'mail' => config('reminders.channels.mail.enabled')
                        ? ['mail' => $settings->user?->email]
                        : [],
                    'slack' => $settings->slackWebhook()
                        ? ['slack' => $settings->slackWebhook()]
                        : [],
                    'discord' => $settings->discordWebhook()
                        ? ['discord' => $settings->discordWebhook()]
                        : [],
                    'push' => $settings->pushEndpoint()
                        ? ['push' => [
                            'endpoint' => $settings->pushEndpoint(),
                            'token' => $settings->pushToken(),
                        ]]
                        : [],
                    default => [],
                };
            })
            ->filter();
    }

    /**
     * Check if we've already sent a notification for this event + channel + date.
     */
    protected function alreadySent(int $userId, int $eventId, string $channel, Carbon $occurrence): bool
    {
        return EventNotificationLog::where('user_id', $userId)
            ->where('event_id', $eventId)
            ->where('channel', $channel)
            ->whereDate('remind_for_date', $occurrence)
            ->whereNotNull('sent_at')
            ->exists();
    }

    /**
     * Dispatch the notification through the correct transport.
     */
    protected function dispatchNotification(
        User $user,
        UpcomingEventReminderNotification $notification,
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
                Notification::route(\App\Notifications\Channels\SlackWebhookChannel::class, $target)->notify($notification);

                return;
            case 'discord':
                Notification::route(\App\Notifications\Channels\DiscordWebhookChannel::class, $target)->notify($notification);

                return;
            case 'push':
                Notification::route(\App\Notifications\Channels\PushWebhookChannel::class, $target)->notify($notification);

                return;
        }
    }

    /**
     * Dispatch notification to queue for processing.
     */
    protected function dispatchQueuedNotification(
        User $user,
        Event $event,
        string $channel,
        string|array|null $target,
        Carbon $occurrence,
        int $daysAway
    ): void {
        if (! $target) {
            return;
        }

        $notification = new UpcomingEventReminderNotification(
            $event,
            $occurrence,
            $user,
            $channel,
            $daysAway
        );

        // Dispatch to queue with proper error handling
        SendNotificationJob::dispatch(
            $user,
            $notification,
            $channel,
            $target,
            $event->id,
            $occurrence
        )->onQueue('notifications');

        // Log the notification dispatch
        Log::info('Notification queued', [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'channel' => $channel,
            'occurrence_date' => $occurrence->toDateString(),
        ]);
    }
}
