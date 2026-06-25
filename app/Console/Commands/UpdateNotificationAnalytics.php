<?php

namespace App\Console\Commands;

use App\Services\NotificationAnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateNotificationAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:update-analytics 
                           {--period=30 : Time period in days to analyze (default: 30)}
                           {--user= : Generate analytics for a specific user ID}
                           {--cache : Cache the analytics results}
                           {--compare : Compare with previous period}
                           {--format=table : Output format (table, json, csv)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update notification analytics and generate reports';

    /**
     * Execute the console command.
     */
    public function handle(NotificationAnalyticsService $analyticsService): int
    {
        $this->info('Updating notification analytics...');

        $period = (int) $this->option('period');
        $userId = $this->option('user');
        $cacheResults = $this->option('cache');
        $compare = $this->option('compare');
        $format = $this->option('format');

        try {
            if ($userId) {
                return $this->updateUserAnalytics($analyticsService, $userId, $period, $cacheResults, $format);
            }

            if ($compare) {
                return $this->generateComparativeAnalytics($analyticsService, $period, $cacheResults, $format);
            }

            return $this->updateSystemAnalytics($analyticsService, $period, $cacheResults, $format);
        } catch (\Exception $e) {
            $this->error('Error updating analytics: '.$e->getMessage());
            Log::error('Analytics update failed', ['error' => $e->getMessage()]);

            return Command::FAILURE;
        }
    }

    /**
     * Update analytics for a specific user.
     */
    protected function updateUserAnalytics(NotificationAnalyticsService $analyticsService, int $userId, int $period, bool $cacheResults, string $format): int
    {
        $user = \App\Models\User::find($userId);

        if (! $user) {
            $this->error("User with ID {$userId} not found.");

            return Command::FAILURE;
        }

        $this->info("Generating analytics for user: {$user->name} (ID: {$userId})");

        $startDate = now()->subDays($period);
        $endDate = now();

        $analytics = $analyticsService->getUserAnalytics($user, $startDate, $endDate);

        if ($cacheResults) {
            $cacheKey = "user_analytics:{$userId}:".$startDate->format('Y-m-d');
            Cache::put($cacheKey, $analytics, 3600); // Cache for 1 hour
            $this->info('Analytics cached for 1 hour.');
        }

        $this->displayAnalytics($analytics, $format, "User Analytics: {$user->name}");

        return Command::SUCCESS;
    }

    /**
     * Update system-wide analytics.
     */
    protected function updateSystemAnalytics(NotificationAnalyticsService $analyticsService, int $period, bool $cacheResults, string $format): int
    {
        $this->info("Generating system analytics for the last {$period} days...");

        $startDate = now()->subDays($period);
        $endDate = now();

        $analytics = $analyticsService->getSystemAnalytics($startDate, $endDate);

        if ($cacheResults) {
            $cacheKey = 'system_analytics:'.$startDate->format('Y-m-d');
            Cache::put($cacheKey, $analytics, 3600); // Cache for 1 hour
            $this->info('Analytics cached for 1 hour.');
        }

        $this->displayAnalytics($analytics, $format, "System Analytics (Last {$period} days)");

        return Command::SUCCESS;
    }

    /**
     * Generate comparative analytics.
     */
    protected function generateComparativeAnalytics(NotificationAnalyticsService $analyticsService, int $period, bool $cacheResults, string $format): int
    {
        $this->info('Generating comparative analytics...');

        $period1End = now();
        $period1Start = now()->subDays($period);

        $period2End = now()->subDays($period);
        $period2Start = now()->subDays($period * 2);

        $comparative = $analyticsService->getComparativeAnalytics(
            $period1Start, $period1End,
            $period2Start, $period2End
        );

        if ($cacheResults) {
            $cacheKey = 'comparative_analytics:'.$period1Start->format('Y-m-d');
            Cache::put($cacheKey, $comparative, 3600); // Cache for 1 hour
            $this->info('Comparative analytics cached for 1 hour.');
        }

        $this->displayComparativeAnalytics($comparative, $format, $period);

        return Command::SUCCESS;
    }

    /**
     * Display analytics in the specified format.
     */
    protected function displayAnalytics(array $analytics, string $format, string $title): void
    {
        switch ($format) {
            case 'json':
                $this->line(json_encode($analytics, JSON_PRETTY_PRINT));
                break;

            case 'csv':
                $this->displayAnalyticsAsCsv($analytics);
                break;

            case 'table':
            default:
                $this->displayAnalyticsAsTable($analytics, $title);
                break;
        }
    }

    /**
     * Display analytics as a table.
     */
    protected function displayAnalyticsAsTable(array $analytics, string $title): void
    {
        $this->info("=== {$title} ===");
        $this->newLine();

        // Overview
        if (isset($analytics['total_notifications'])) {
            $this->info('Overview:');
            $this->table(['Metric', 'Value'], [
                ['Total Notifications', $analytics['total_notifications']],
                ['Success Rate', $analytics['success_rate'].'%'],
                ['Average per Day', number_format($analytics['average_per_day'], 2)],
            ]);
        }

        // User-specific metrics
        if (isset($analytics['user_id'])) {
            $this->info('User Information:');
            $this->table(['Metric', 'Value'], [
                ['User ID', $analytics['user_id']],
                ['Most Active Day', $analytics['most_active_day'] ?? 'N/A'],
            ]);
        }

        // System-wide metrics
        if (isset($analytics['total_users'])) {
            $this->info('System Information:');
            $this->table(['Metric', 'Value'], [
                ['Total Users', $analytics['total_users']],
                ['Active Users', $analytics['active_users']],
                ['Average per User', number_format($analytics['average_per_user'], 2)],
            ]);
        }

        // Channel breakdown
        if (! empty($analytics['by_channel'])) {
            $this->info('Channel Breakdown:');
            $channelData = [];
            foreach ($analytics['by_channel'] as $channel => $count) {
                $channelData[] = [$channel, $count];
            }
            $this->table(['Channel', 'Count'], $channelData);
        }

        // Daily breakdown
        if (! empty($analytics['by_day'])) {
            $this->info('Daily Breakdown (last 10 days):');
            $dailyData = array_slice($analytics['by_day'], -10, 10, true);
            $tableData = [];
            foreach ($dailyData as $date => $count) {
                $tableData[] = [$date, $count];
            }
            $this->table(['Date', 'Notifications'], $tableData);
        }

        // Top users
        if (! empty($analytics['top_users'])) {
            $this->info('Top Users by Notification Count:');
            $topUserData = [];
            foreach ($analytics['top_users'] as $userId => $count) {
                $user = \App\Models\User::find($userId);
                $userName = $user ? $user->name : "User {$userId}";
                $topUserData[] = [$userName, $count];
            }
            $this->table(['User', 'Count'], $topUserData);
        }

        // Channel distribution
        if (! empty($analytics['channel_distribution'])) {
            $this->info('Channel Distribution:');
            $distData = [];
            foreach ($analytics['channel_distribution'] as $channel => $count) {
                $distData[] = [$channel, $count];
            }
            $this->table(['Channel', 'Users'], $distData);
        }
    }

    /**
     * Display analytics as CSV.
     */
    protected function displayAnalyticsAsCsv(array $analytics): void
    {
        $this->line('metric,value');

        if (isset($analytics['total_notifications'])) {
            $this->line("total_notifications,{$analytics['total_notifications']}");
            $this->line("success_rate,{$analytics['success_rate']}");
            $this->line("average_per_day,{$analytics['average_per_day']}");
        }

        if (isset($analytics['total_users'])) {
            $this->line("total_users,{$analytics['total_users']}");
            $this->line("active_users,{$analytics['active_users']}");
            $this->line("average_per_user,{$analytics['average_per_user']}");
        }

        if (! empty($analytics['by_channel'])) {
            foreach ($analytics['by_channel'] as $channel => $count) {
                $this->line("channel_{$channel},{$count}");
            }
        }
    }

    /**
     * Display comparative analytics.
     */
    protected function displayComparativeAnalytics(array $comparative, string $format, int $period): void
    {
        $title = "Comparative Analytics (Last {$period} days vs Previous {$period} days)";

        if ($format === 'json') {
            $this->line(json_encode($comparative, JSON_PRETTY_PRINT));

            return;
        }

        $this->info("=== {$title} ===");
        $this->newLine();

        $this->info('Changes:');
        $changes = $comparative['changes'];

        $changeData = [
            ['Total Notifications', $changes['total_notifications'].'%'],
            ['Active Users', $changes['active_users'].'%'],
            ['Average per Day', $changes['average_per_day'].'%'],
        ];

        $this->table(['Metric', 'Change'], $changeData);

        $this->newLine();
        $this->info('Period 1 (Most Recent):');
        $this->displayAnalyticsAsTable($comparative['period1'], '');

        $this->newLine();
        $this->info('Period 2 (Previous):');
        $this->displayAnalyticsAsTable($comparative['period2'], '');
    }
}
