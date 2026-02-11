<?php

namespace App\Services;

use App\Models\EventNotificationLog;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChannelHealthService
{
    /**
     * Check the health of a specific channel for a user.
     */
    public function checkChannelHealth(User $user, string $channel): array
    {
        $health = [
            'channel' => $channel,
            'status' => 'unknown',
            'last_used' => null,
            'success_rate' => 0,
            'error_count' => 0,
            'total_attempts' => 0,
            'connectivity' => false,
            'details' => [],
        ];

        $settings = $user->notificationSetting ?: $user->notificationSetting()->first();
        if (! $settings) {
            $health['status'] = 'inactive';
            $health['details'][] = 'Notification settings missing';

            return $health;
        }

        if (! $this->hasChannelConfiguration($settings, $channel)) {
            $health['status'] = 'inactive';
            $health['details'][] = 'Channel configuration missing';

            return $health;
        }

        // Get recent notification logs for this channel
        $recentLogs = EventNotificationLog::where('user_id', $user->id)
            ->where('channel', $channel)
            ->where('sent_at', '>=', now()->subDays(7))
            ->orderBy('sent_at', 'desc')
            ->get();

        if ($recentLogs->isEmpty()) {
            $health['status'] = 'inactive';
            $health['details'][] = 'No recent activity';
        } else {
            $health['last_used'] = $recentLogs->first()->sent_at;
            $health['total_attempts'] = $recentLogs->count();

            // Calculate success rate (assuming all sent notifications are successful)
            $health['success_rate'] = 100;
            $health['status'] = 'healthy';
        }

        // Test connectivity
        $health['connectivity'] = $this->testChannelConnectivity($settings, $channel);

        if (! $health['connectivity']) {
            $health['status'] = 'unhealthy';
            $health['details'][] = 'Connectivity test failed';
        }

        return $health;
    }

    /**
     * Check health for all channels of a user.
     */
    public function checkAllChannelsHealth(User $user): array
    {
        $settings = $user->notificationSetting ?: $user->notificationSetting()->first();
        $channels = $settings?->resolved_channels ?? [];
        $healthResults = [];

        foreach ($channels as $channel) {
            $healthResults[$channel] = $this->checkChannelHealth($user, $channel);
        }

        return $healthResults;
    }

    /**
     * Get overall health status for all users.
     */
    public function getSystemHealthOverview(): array
    {
        $overview = [
            'total_users' => User::count(),
            'active_users' => 0,
            'channels' => [
                'mail' => ['healthy' => 0, 'unhealthy' => 0, 'inactive' => 0],
                'slack' => ['healthy' => 0, 'unhealthy' => 0, 'inactive' => 0],
                'discord' => ['healthy' => 0, 'unhealthy' => 0, 'inactive' => 0],
                'push' => ['healthy' => 0, 'unhealthy' => 0, 'inactive' => 0],
            ],
            'last_check' => now(),
        ];

        $users = User::with('notificationSetting')->get();

        foreach ($users as $user) {
            if (! $user->notificationSetting) {
                continue;
            }

            $channels = $user->notificationSetting->resolved_channels ?? [];

            if (! empty($channels)) {
                $overview['active_users']++;
            }

            foreach ($channels as $channel) {
                $health = $this->checkChannelHealth($user, $channel);
                $overview['channels'][$channel][$health['status']]++;
            }
        }

        return $overview;
    }

    /**
     * Test connectivity for a specific channel.
     */
    protected function testChannelConnectivity(NotificationSetting $settings, string $channel): bool
    {
        return match ($channel) {
            'mail' => $this->testMailConnectivity(),
            'slack' => $this->testWebhookConnectivity($settings->slackWebhook(), 'slack'),
            'discord' => $this->testWebhookConnectivity($settings->discordWebhook(), 'discord'),
            'push' => $this->testPushConnectivity($settings->pushEndpoint()),
            default => false,
        };
    }

    /**
     * Test mail connectivity.
     */
    protected function testMailConnectivity(): bool
    {
        try {
            // This is a basic check - in production you might want to test actual mail sending
            $config = config('mail');

            return ! empty($config['host']) &&
                   ! empty($config['port']) &&
                   ! empty($config['username']) &&
                   ! empty($config['password']);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Test webhook connectivity.
     */
    protected function testWebhookConnectivity(?string $url, string $type): bool
    {
        if (! $url) {
            return false;
        }

        $cacheKey = "webhook_health:{$type}:".md5($url);

        return Cache::remember($cacheKey, 300, function () use ($url, $type) {
            try {
                $webhookValidator = new WebhookValidationService;

                return $webhookValidator->testWebhookConnectivity($url, $type);
            } catch (\Exception) {
                return false;
            }
        });
    }

    /**
     * Test push connectivity.
     */
    protected function testPushConnectivity(?string $endpoint): bool
    {
        if (! $endpoint) {
            return false;
        }

        try {
            $response = Http::timeout(5)->head($endpoint);

            return $response->successful();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get users with unhealthy channels.
     */
    public function getUsersWithUnhealthyChannels(): Collection
    {
        $unhealthyUsers = [];
        $users = User::with('notificationSetting')->get();

        foreach ($users as $user) {
            if (! $user->notificationSetting) {
                continue;
            }

            $channels = $user->notificationSetting->resolved_channels ?? [];
            $hasUnhealthy = false;

            foreach ($channels as $channel) {
                $health = $this->checkChannelHealth($user, $channel);
                if ($health['status'] === 'unhealthy') {
                    $hasUnhealthy = true;
                    break;
                }
            }

            if ($hasUnhealthy) {
                $unhealthyUsers[] = $user;
            }
        }

        return new Collection($unhealthyUsers);
    }

    /**
     * Determine whether a channel has enough configuration to be checked.
     */
    protected function hasChannelConfiguration(NotificationSetting $settings, string $channel): bool
    {
        return match ($channel) {
            'mail' => true,
            'slack' => $settings->slackWebhook() !== null,
            'discord' => $settings->discordWebhook() !== null,
            'push' => $settings->pushEndpoint() !== null && $settings->pushToken() !== null,
            default => false,
        };
    }

    /**
     * Log channel health issues.
     */
    public function logHealthIssue(User $user, string $channel, string $issue): void
    {
        Log::channel('notifications')->warning('Channel health issue', [
            'user_id' => $user->id,
            'channel' => $channel,
            'issue' => $issue,
            'timestamp' => now(),
        ]);
    }
}
