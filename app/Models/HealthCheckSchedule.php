<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthCheckSchedule extends Model
{
    /** @use HasFactory<\Database\Factories\HealthCheckScheduleFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notification_health_checks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'channel',
        'check_type',
        'status',
        'details',
        'response_time_ms',
        'checked_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response_time_ms' => 'integer',
            'checked_at' => 'datetime',
            'details' => 'array',
        ];
    }

    /**
     * Get the outages for this channel.
     */
    public function outages(): HasMany
    {
        return $this->hasMany(NotificationOutage::class, 'channel', 'channel');
    }

    /**
     * Get the metrics for this channel.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(NotificationMetric::class, 'channel', 'channel');
    }

    /**
     * Scope a query to only include health checks with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include health checks for a specific channel.
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope a query to only include health checks of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('check_type', $type);
    }

    /**
     * Scope a query to only include recent health checks.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('checked_at', '>=', now()->subHours($hours));
    }

    /**
     * Check if the health check indicates a healthy status.
     */
    public function isHealthy(): bool
    {
        return in_array($this->status, ['healthy', 'ok', 'success'], true);
    }

    /**
     * Check if the health check indicates a warning status.
     */
    public function isWarning(): bool
    {
        return in_array($this->status, ['warning', 'degraded'], true);
    }

    /**
     * Check if the health check indicates a critical status.
     */
    public function isCritical(): bool
    {
        return in_array($this->status, ['critical', 'error', 'failed'], true);
    }
}
