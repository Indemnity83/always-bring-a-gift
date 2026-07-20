<?php

namespace App\Livewire\Admin;

use App\Models\ChannelHealth;
use App\Models\EventNotificationLog;
use App\Models\NotificationAnalytics;
use App\Models\NotificationMetric;
use App\Models\NotificationOutage;
use App\Models\NotificationRateLimit;
use App\Services\ChannelHealthService;
use App\Services\NotificationAnalyticsService;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationStatus extends Component
{
    use WithPagination;

    public string $timeRange = '24h';

    public string $selectedChannel = 'all';

    public array $channelFilters = [];

    public bool $autoRefresh = true;

    public int $refreshInterval = 30;

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public array $lastHealthCheck = [];

    public array $lastAnalytics = [];

    protected $listeners = ['refreshNotificationStatus' => '$refresh'];

    public function mount(): void
    {
        $this->loadChannelFilters();
    }

    public function loadChannelFilters(): void
    {
        $this->channelFilters = [
            'all' => 'All Channels',
            'mail' => 'Email',
            'slack' => 'Slack',
            'discord' => 'Discord',
            'push' => 'Push Notifications',
        ];
    }

    public function setTimeRange(string $range): void
    {
        $this->timeRange = $range;
        $this->resetPage();
    }

    public function setChannel(string $channel): void
    {
        $this->selectedChannel = $channel;
        $this->resetPage();
    }

    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = ! $this->autoRefresh;
    }

    public function refreshData(): void
    {
        $this->dispatch('$refresh');
    }

    public function runHealthCheck(ChannelHealthService $healthService): void
    {
        $this->lastHealthCheck = $healthService->getSystemHealthOverview();
        session()->flash('status', 'Health checks completed successfully.');
    }

    public function updateAnalytics(NotificationAnalyticsService $analyticsService): void
    {
        $this->lastAnalytics = $analyticsService->getSystemAnalytics();
        session()->flash('status', 'Analytics updated successfully.');
    }

    public function getDateRange(): array
    {
        return match ($this->timeRange) {
            '1h' => [now()->subHour(), now()],
            '24h' => [now()->subDay(), now()],
            '7d' => [now()->subWeek(), now()],
            '30d' => [now()->subDays(30), now()],
            '90d' => [now()->subDays(90), now()],
            default => [now()->subDay(), now()],
        };
    }

    public function getSystemHealthProperty(): Collection
    {
        return ChannelHealth::with(['outages', 'metrics'])
            ->recent(48)
            ->latest()
            ->get()
            ->groupBy('channel');
    }

    public function getNotificationMetricsProperty(): Collection
    {
        [$startDate, $endDate] = $this->getDateRange();

        $query = NotificationMetric::whereBetween('date', [$startDate, $endDate]);

        if ($this->selectedChannel !== 'all') {
            $query->where('channel', $this->selectedChannel);
        }

        return $query->orderByDesc('date')->get();
    }

    public function getRecentNotificationsProperty()
    {
        [$startDate, $endDate] = $this->getDateRange();

        $query = EventNotificationLog::with(['user', 'event'])
            ->whereBetween('sent_at', [$startDate, $endDate]);

        if ($this->selectedChannel !== 'all') {
            $query->where('channel', $this->selectedChannel);
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);
    }

    public function getAnalyticsDataProperty(): array
    {
        [$startDate, $endDate] = $this->getDateRange();

        $query = NotificationAnalytics::whereBetween('date', [$startDate, $endDate]);

        if ($this->selectedChannel !== 'all') {
            $query->where('channel', $this->selectedChannel);
        }

        $analytics = $query->orderByDesc('date')->get();

        return [
            'total_sent' => $analytics->sum('sent_count'),
            'total_delivered' => $analytics->sum('delivered_count'),
            'total_failed' => $analytics->sum('failed_count'),
            'total_read' => $analytics->sum('read_count'),
            'total_clicked' => $analytics->sum('click_count'),
            'avg_delivery_rate' => $analytics->avg('delivery_rate') ?? 0,
            'avg_open_rate' => $analytics->avg('open_rate') ?? 0,
            'avg_click_rate' => $analytics->avg('click_rate') ?? 0,
            'avg_delivery_time' => $analytics->avg('avg_delivery_time') ?? 0,
        ];
    }

    public function getRateLimitStatsProperty(): array
    {
        $activeLimits = NotificationRateLimit::where('is_blocked', true)
            ->whereNotNull('reset_at')
            ->where('reset_at', '>', now())
            ->get();

        $attemptsByChannel = $activeLimits->groupBy('channel')
            ->map(fn ($group) => $group->sum('attempts'))
            ->sortDesc();

        return [
            'active_limits' => $activeLimits->count(),
            'total_hits' => $activeLimits->sum('attempts'),
            'channels_affected' => $activeLimits->pluck('channel')->unique()->count(),
            'most_hit_channel' => $attemptsByChannel->keys()->first(),
        ];
    }

    public function getSystemOutagesProperty(): Collection
    {
        return NotificationOutage::where('is_resolved', false)
            ->orWhere(function ($query) {
                $query->where('is_resolved', true)
                    ->where('ended_at', '>', now()->subDay());
            })
            ->latest('started_at')
            ->get();
    }

    public function getHealthSummaryProperty(): array
    {
        $systemHealth = $this->systemHealth;
        $totalChannels = $systemHealth->count();
        $healthyChannels = 0;
        $warningChannels = 0;
        $criticalChannels = 0;

        foreach ($systemHealth as $healthChecks) {
            $latestCheck = $healthChecks->first();
            if ($latestCheck) {
                if ($latestCheck->isHealthy()) {
                    $healthyChannels++;
                } elseif ($latestCheck->isWarning()) {
                    $warningChannels++;
                } elseif ($latestCheck->isCritical()) {
                    $criticalChannels++;
                }
            }
        }

        return [
            'total_channels' => $totalChannels,
            'healthy_channels' => $healthyChannels,
            'warning_channels' => $warningChannels,
            'critical_channels' => $criticalChannels,
            'health_percentage' => $totalChannels > 0 ? ($healthyChannels / $totalChannels) * 100 : 0,
        ];
    }

    public function getChannelChartDataProperty(): array
    {
        $analytics = NotificationAnalytics::lastDays(7)
            ->get()
            ->groupBy('channel');

        $chartData = [];
        foreach ($analytics as $channel => $channelAnalytics) {
            $chartData[$channel] = [
                'labels' => $channelAnalytics->pluck('date')->map(fn ($date) => $date->format('M d'))->toArray(),
                'sent' => $channelAnalytics->pluck('sent_count')->toArray(),
                'delivered' => $channelAnalytics->pluck('delivered_count')->toArray(),
                'failed' => $channelAnalytics->pluck('failed_count')->toArray(),
            ];
        }

        return $chartData;
    }

    public function sortBy(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function render()
    {
        return view('livewire.admin.notification-status');
    }
}
