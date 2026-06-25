<?php

namespace App\Services;

use App\Models\EventNotificationLog;
use App\Models\NotificationSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class NotificationAnalyticsService
{
    /**
     * Get notification analytics for a specific user.
     */
    public function getUserAnalytics(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $logs = EventNotificationLog::where('user_id', $user->id)
            ->whereBetween('sent_at', [$startDate, $endDate])
            ->get();

        $analytics = [
            'user_id' => $user->id,
            'period' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
            ],
            'total_notifications' => $logs->count(),
            'by_channel' => $this->getChannelBreakdown($logs),
            'by_day' => $this->getDailyBreakdown($logs),
            'success_rate' => $this->calculateSuccessRate($logs),
            'average_per_day' => $this->calculateAveragePerDay($logs, $startDate, $endDate),
            'most_active_day' => $this->getMostActiveDay($logs),
            'channel_preferences' => $this->getChannelPreferences($user),
        ];

        return $analytics;
    }

    /**
     * Get system-wide notification analytics.
     */
    public function getSystemAnalytics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $logs = EventNotificationLog::whereBetween('sent_at', [$startDate, $endDate])
            ->get();

        $totalUsers = User::count();
        $activeUsers = NotificationSetting::distinct('user_id')->count('user_id');

        $analytics = [
            'period' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
            ],
            'total_notifications' => $logs->count(),
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'by_channel' => $this->getChannelBreakdown($logs),
            'by_day' => $this->getDailyBreakdown($logs),
            'success_rate' => $this->calculateSuccessRate($logs),
            'average_per_user' => (float) $logs->count() / max(1, $totalUsers),
            'average_per_day' => $this->calculateAveragePerDay($logs, $startDate, $endDate),
            'top_users' => $this->getTopUsers($logs),
            'channel_distribution' => $this->getSystemChannelDistribution(),
            'growth_trend' => $this->getGrowthTrend($startDate, $endDate),
        ];

        return $analytics;
    }

    /**
     * Get channel breakdown for notification logs.
     */
    protected function getChannelBreakdown(Collection $logs): array
    {
        $counts = $logs->groupBy('channel')
            ->map(fn ($group) => $group->count())
            ->toArray();

        $order = ['mail', 'slack', 'discord', 'push'];
        $sorted = [];

        foreach ($order as $channel) {
            if (array_key_exists($channel, $counts)) {
                $sorted[$channel] = $counts[$channel];
            }
        }

        foreach ($counts as $channel => $count) {
            if (! array_key_exists($channel, $sorted)) {
                $sorted[$channel] = $count;
            }
        }

        return $sorted;
    }

    /**
     * Get daily breakdown for notification logs.
     */
    protected function getDailyBreakdown(Collection $logs): array
    {
        return $logs->groupBy(fn ($log) => $log->sent_at->format('Y-m-d'))
            ->map(fn ($group) => $group->count())
            ->sortKeys()
            ->toArray();
    }

    /**
     * Calculate success rate for notification logs.
     */
    protected function calculateSuccessRate(Collection $logs): float
    {
        if ($logs->isEmpty()) {
            return 0.0;
        }

        // Assuming all sent notifications are successful
        // In a real implementation, you might track failed notifications
        return 100.0;
    }

    /**
     * Calculate average notifications per day.
     */
    protected function calculateAveragePerDay(Collection $logs, Carbon $startDate, Carbon $endDate): float
    {
        $days = max(1, $startDate->diffInDays($endDate));

        return $logs->count() / $days;
    }

    /**
     * Get most active day for notifications.
     */
    protected function getMostActiveDay(Collection $logs): ?string
    {
        if ($logs->isEmpty()) {
            return null;
        }

        $dailyCounts = $logs->groupBy(fn ($log) => $log->sent_at->format('Y-m-d'))
            ->map(fn ($group) => $group->count());

        return $dailyCounts->sortDesc()->keys()->first();
    }

    /**
     * Get channel preferences for a user.
     */
    protected function getChannelPreferences(User $user): array
    {
        $settings = $user->notificationSetting;
        $channels = $settings?->resolved_channels ?? [];

        return [
            'enabled_channels' => $channels,
            'has_mail' => in_array('mail', $channels),
            'has_slack' => in_array('slack', $channels),
            'has_discord' => in_array('discord', $channels),
            'has_push' => in_array('push', $channels),
        ];
    }

    /**
     * Get top users by notification count.
     */
    protected function getTopUsers(Collection $logs): array
    {
        return $logs->groupBy('user_id')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->take(10)
            ->toArray();
    }

    /**
     * Get system-wide channel distribution.
     */
    protected function getSystemChannelDistribution(): array
    {
        $distribution = [
            'mail' => 0,
            'slack' => 0,
            'discord' => 0,
            'push' => 0,
        ];

        NotificationSetting::chunk(200, function (Collection $settings) use (&$distribution): void {
            foreach ($settings as $setting) {
                $channels = $setting->resolved_channels ?? [];

                foreach ($channels as $channel) {
                    if (isset($distribution[$channel])) {
                        $distribution[$channel]++;
                    }
                }
            }
        });

        return $distribution;
    }

    /**
     * Get growth trend over time.
     */
    protected function getGrowthTrend(Carbon $startDate, Carbon $endDate): array
    {
        $rangeStart = $startDate->copy()->startOfDay();
        $rangeEnd = $endDate->copy()->endOfDay();

        $counts = EventNotificationLog::whereBetween('sent_at', [$rangeStart, $rangeEnd])
            ->selectRaw('date(sent_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $trend = [];
        $currentDate = $rangeStart->copy();
        $lastDate = $rangeEnd->copy()->startOfDay();

        while ($currentDate->lte($lastDate)) {
            $key = $currentDate->format('Y-m-d');
            $trend[$key] = (int) ($counts[$key] ?? 0);
            $currentDate->addDay();
        }

        return $trend;
    }

    /**
     * Get notification statistics for a specific time period.
     */
    public function getTimeBasedStats(string $period): array
    {
        $endDate = now();
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subDays(30),
        };

        return $this->getSystemAnalytics($startDate, $endDate);
    }

    /**
     * Get comparative analytics between two time periods.
     */
    public function getComparativeAnalytics(Carbon $period1Start, Carbon $period1End, Carbon $period2Start, Carbon $period2End): array
    {
        $period1 = $this->getSystemAnalytics($period1Start, $period1End);
        $period2 = $this->getSystemAnalytics($period2Start, $period2End);

        return [
            'period1' => $period1,
            'period2' => $period2,
            'changes' => [
                'total_notifications' => $this->calculateChange($period1['total_notifications'], $period2['total_notifications']),
                'active_users' => $this->calculateChange($period1['active_users'], $period2['active_users']),
                'average_per_day' => $this->calculateChange($period1['average_per_day'], $period2['average_per_day']),
            ],
        ];
    }

    /**
     * Calculate percentage change between two values.
     */
    protected function calculateChange(float $value1, float $value2): float
    {
        if ($value2 == 0) {
            return $value1 > 0 ? 100.0 : 0.0;
        }

        return (($value1 - $value2) / $value2) * 100;
    }
}
