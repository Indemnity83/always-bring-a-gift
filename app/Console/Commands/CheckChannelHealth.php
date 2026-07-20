<?php

namespace App\Console\Commands;

use App\Services\ChannelHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckChannelHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check-health 
                           {--user= : Check health for a specific user ID}
                           {--channel= : Check health for a specific channel}
                           {--report : Generate a detailed health report}
                           {--log-issues : Log health issues to the notification log}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health of notification channels';

    /**
     * Execute the console command.
     */
    public function handle(ChannelHealthService $healthService): int
    {
        $this->info('Checking notification channel health...');

        $userId = $this->option('user');
        $channel = $this->option('channel');
        $generateReport = $this->option('report');
        $logIssues = $this->option('log-issues');

        try {
            if ($userId) {
                return $this->checkUserHealth($healthService, $userId, $channel, $logIssues);
            }

            if ($generateReport) {
                return $this->generateSystemHealthReport($healthService);
            }

            return $this->checkSystemHealth($healthService, $channel, $logIssues);
        } catch (\Exception $e) {
            $this->error('Error checking channel health: '.$e->getMessage());
            Log::error('Channel health check failed', ['error' => $e->getMessage()]);

            return Command::FAILURE;
        }
    }

    /**
     * Check health for a specific user.
     */
    protected function checkUserHealth(ChannelHealthService $healthService, int $userId, ?string $channel, bool $logIssues): int
    {
        $user = \App\Models\User::find($userId);

        if (! $user) {
            $this->error("User with ID {$userId} not found.");

            return Command::FAILURE;
        }

        $this->info("Checking health for user: {$user->name} (ID: {$userId})");

        if ($channel) {
            $health = $healthService->checkChannelHealth($user, $channel);
            $this->displayChannelHealth($health);

            if ($logIssues && $health['status'] === 'unhealthy') {
                $healthService->logHealthIssue($user, $channel, 'Health check failed');
            }
        } else {
            $healthResults = $healthService->checkAllChannelsHealth($user);

            foreach ($healthResults as $channelName => $health) {
                $this->displayChannelHealth($health);

                if ($logIssues && $health['status'] === 'unhealthy') {
                    $healthService->logHealthIssue($user, $channelName, 'Health check failed');
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Check system-wide health.
     */
    protected function checkSystemHealth(ChannelHealthService $healthService, ?string $channel, bool $logIssues): int
    {
        $this->info('Checking system-wide channel health...');

        $overview = $healthService->getSystemHealthOverview();

        $this->info("Total users: {$overview['total_users']}");
        $this->info("Active users: {$overview['active_users']}");
        $this->info("Last check: {$overview['last_check']}");

        $this->newLine();
        $this->info('Channel Health Summary:');

        foreach ($overview['channels'] as $channelName => $stats) {
            if ($channel && $channelName !== $channel) {
                continue;
            }

            $this->line("  {$channelName}:");
            $this->line("    Healthy: {$stats['healthy']}");
            $this->line("    Unhealthy: {$stats['unhealthy']}");
            $this->line("    Inactive: {$stats['inactive']}");
        }

        if ($logIssues) {
            $unhealthyUsers = $healthService->getUsersWithUnhealthyChannels();

            foreach ($unhealthyUsers as $user) {
                $userChannels = $healthService->checkAllChannelsHealth($user);

                foreach ($userChannels as $channelName => $health) {
                    if ($health['status'] === 'unhealthy') {
                        $healthService->logHealthIssue($user, $channelName, 'System health check failed');
                    }
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Generate detailed system health report.
     */
    protected function generateSystemHealthReport(ChannelHealthService $healthService): int
    {
        $this->info('Generating detailed system health report...');

        $overview = $healthService->getSystemHealthOverview();
        $unhealthyUsers = $healthService->getUsersWithUnhealthyChannels();

        $this->info('=== SYSTEM HEALTH REPORT ===');
        $this->info('Generated at: '.now());
        $this->newLine();

        $this->info('Overview:');
        $this->line("  Total Users: {$overview['total_users']}");
        $this->line("  Active Users: {$overview['active_users']}");
        $this->line("  Users with Issues: {$unhealthyUsers->count()}");
        $this->newLine();

        $this->info('Channel Status:');
        foreach ($overview['channels'] as $channelName => $stats) {
            $total = $stats['healthy'] + $stats['unhealthy'] + $stats['inactive'];
            $healthPercentage = $total > 0 ? ($stats['healthy'] / $total) * 100 : 0;

            $this->line("  {$channelName}:");
            $this->line("    Total: {$total}");
            $this->line("    Healthy: {$stats['healthy']} ({$healthPercentage}%)");
            $this->line("    Unhealthy: {$stats['unhealthy']}");
            $this->line("    Inactive: {$stats['inactive']}");
        }

        if ($unhealthyUsers->count() > 0) {
            $this->newLine();
            $this->info('Users with Unhealthy Channels:');

            foreach ($unhealthyUsers as $user) {
                $this->line("  - {$user->name} (ID: {$user->id}, Email: {$user->email})");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Display channel health information.
     */
    protected function displayChannelHealth(array $health): void
    {
        $statusColor = match ($health['status']) {
            'healthy' => 'green',
            'unhealthy' => 'red',
            'inactive' => 'yellow',
            default => 'gray',
        };

        $this->line("  Channel: <fg={$statusColor}>{$health['channel']}</>");
        $this->line("    Status: <fg={$statusColor}>{$health['status']}</>");
        $this->line('    Connectivity: '.($health['connectivity'] ? '<fg=green>✓</>' : '<fg=red>✗</>'));
        $this->line("    Success Rate: {$health['success_rate']}%");
        $this->line("    Total Attempts: {$health['total_attempts']}");

        if ($health['last_used']) {
            $this->line("    Last Used: {$health['last_used']}");
        }

        if (! empty($health['details'])) {
            $this->line('    Details: '.implode(', ', $health['details']));
        }

        $this->newLine();
    }
}
