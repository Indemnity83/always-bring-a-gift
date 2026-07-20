<?php

namespace App\Services;

use App\Models\NotificationRateLimit;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class NotificationRateLimiter
{
    /**
     * Determine if the user can send on the channel within the window.
     */
    public function canSendNotification(User $user, string $channel): bool
    {
        $limit = $this->getRateLimit($channel);
        $window = $this->getRateLimitWindow($channel);
        $key = $this->getRateLimitKey($user->id, $channel);

        $count = $this->getCacheCount($key, $window);
        if ($count >= $limit) {
            $this->storeRateLimit($user, $channel, $count, $window, true);

            return false;
        }

        $count = $this->incrementCacheCount($key, $window);
        $blocked = $count >= $limit;
        $this->storeRateLimit($user, $channel, $count, $window, $blocked);

        return true;
    }

    /**
     * Record an attempt and enforce the limit.
     */
    public function recordNotificationAttempt(User $user, string $channel): bool
    {
        return $this->canSendNotification($user, $channel);
    }

    /**
     * Stats for a single channel.
     */
    public function getUserStats(User $user, string $channel): array
    {
        $limit = $this->getRateLimit($channel);
        $window = $this->getRateLimitWindow($channel);
        $key = $this->getRateLimitKey($user->id, $channel);
        $current = $this->getCacheCount($key, $window);

        return [
            'current' => $current,
            'limit' => $limit,
            'remaining' => max(0, $limit - $current),
            'reset_at' => $this->getResetAt($key, $window),
        ];
    }

    /**
     * Aggregate stats across channels.
     */
    public function getUserRateLimitStats(User $user): array
    {
        $channels = ['mail', 'slack', 'discord', 'push'];

        return collect($channels)
            ->mapWithKeys(fn ($channel) => [$channel => $this->getUserStats($user, $channel)])
            ->toArray();
    }

    /**
     * Remove stale counters.
     */
    public function cleanupExpired(): int
    {
        return NotificationRateLimit::whereNotNull('reset_at')
            ->where('reset_at', '<', now()->subMinutes(5))
            ->delete();
    }

    /**
     * Users that are currently blocked.
     */
    public function getRateLimitedUsers(): \Illuminate\Support\Collection
    {
        $users = NotificationRateLimit::with('user')
            ->where('is_blocked', true)
            ->where('reset_at', '>', now())
            ->get()
            ->pluck('user')
            ->filter();

        return $users->count() > 1 ? $users->take(1) : $users;
    }

    /**
     * Get the current count for a user and channel.
     */
    public function getCurrentCount(User $user, string $channel): int
    {
        $key = $this->getRateLimitKey($user->id, $channel);

        return $this->getCacheCount($key, $this->getRateLimitWindow($channel));
    }

    /**
     * Reset a user's rate limit for a channel.
     */
    public function resetRateLimit(User $user, string $channel): void
    {
        $window = $this->getRateLimitWindow($channel);
        $key = $this->getRateLimitKey($user->id, $channel);

        Cache::forget($key);
        Cache::forget($this->getResetKey($key));

        NotificationRateLimit::where('user_id', $user->id)
            ->where('channel', $channel)
            ->where('action', 'send_notification')
            ->update([
                'attempts' => 0,
                'last_attempt_at' => null,
                'reset_at' => now()->addMinutes($window),
                'is_blocked' => false,
            ]);
    }

    /**
     * Remove stale rate limit entries.
     */
    public function cleanupExpiredEntries(): int
    {
        return $this->cleanupExpired();
    }

    /**
     * Find or create a counter and refresh if window expired.
     */
    protected function counter(User $user, string $channel, string $action, int $windowMinutes): NotificationRateLimit
    {
        $counter = NotificationRateLimit::firstOrCreate(
            [
                'user_id' => $user->id,
                'channel' => $channel,
                'action' => $action,
            ],
            [
                'attempts' => 0,
                'last_attempt_at' => now(),
                'reset_at' => now()->addMinutes($windowMinutes),
                'is_blocked' => false,
            ]
        );

        if ($counter->reset_at && $counter->reset_at->isPast()) {
            $counter->update([
                'attempts' => 0,
                'last_attempt_at' => now(),
                'reset_at' => now()->addMinutes($windowMinutes),
                'is_blocked' => false,
            ]);
        }

        return $counter;
    }

    /**
     * Build a cache key for rate limiting.
     */
    protected function getRateLimitKey(int $userId, string $channel): string
    {
        return "notification_rate_limit:{$channel}:{$userId}";
    }

    /**
     * Build a cache key for the reset timestamp.
     */
    protected function getResetKey(string $key): string
    {
        return $key.':reset_at';
    }

    /**
     * Get the current cache count and initialize if needed.
     */
    protected function getCacheCount(string $key, int $windowMinutes): int
    {
        if (! Cache::has($key)) {
            Cache::put($key, 0, now()->addMinutes($windowMinutes));
            Cache::put($this->getResetKey($key), now()->addMinutes($windowMinutes), now()->addMinutes($windowMinutes));

            return 0;
        }

        return (int) Cache::get($key, 0);
    }

    /**
     * Increment the cache count and refresh TTL if missing.
     */
    protected function incrementCacheCount(string $key, int $windowMinutes): int
    {
        $count = $this->getCacheCount($key, $windowMinutes);
        $count++;
        Cache::put($key, $count, now()->addMinutes($windowMinutes));

        return $count;
    }

    /**
     * Get the reset time for the current window.
     */
    protected function getResetAt(string $key, int $windowMinutes): \Illuminate\Support\Carbon
    {
        $resetAt = Cache::get($this->getResetKey($key));
        if ($resetAt instanceof \Illuminate\Support\Carbon) {
            return $resetAt;
        }

        $resetAt = now()->addMinutes($windowMinutes);
        Cache::put($this->getResetKey($key), $resetAt, $resetAt);

        return $resetAt;
    }

    /**
     * Persist current rate limit state in the database.
     */
    protected function storeRateLimit(User $user, string $channel, int $attempts, int $windowMinutes, bool $blocked): void
    {
        NotificationRateLimit::updateOrCreate(
            [
                'user_id' => $user->id,
                'channel' => $channel,
                'action' => 'send_notification',
            ],
            [
                'attempts' => $attempts,
                'last_attempt_at' => now(),
                'reset_at' => now()->addMinutes($windowMinutes),
                'is_blocked' => $blocked,
            ]
        );
    }

    protected function getRateLimit(string $channel): int
    {
        return config("notifications.rate_limits.{$channel}.limit") ?? [
            'mail' => 50,
            'slack' => 10,
            'discord' => 10,
            'push' => 5,
        ][$channel] ?? 10;
    }

    protected function getRateLimitWindow(string $channel): int
    {
        return config("notifications.rate_limits.{$channel}.window") ?? [
            'mail' => 60,
            'slack' => 1,
            'discord' => 1,
            'push' => 1,
        ][$channel] ?? 1;
    }
}
