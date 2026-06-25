<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationRateLimitConfig extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationRateLimitConfigFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'channel',
        'action',
        'max_attempts',
        'window_minutes',
        'block_duration_minutes',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_attempts' => 'integer',
            'window_minutes' => 'integer',
            'block_duration_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include active configurations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include configurations for a specific channel.
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope a query to only include configurations for a specific action.
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Get the rate limits for this configuration.
     */
    public function rateLimits()
    {
        return $this->hasMany(NotificationRateLimit::class, 'channel', 'channel')
            ->where('action', $this->action);
    }

    /**
     * Get the reset time for a rate limit based on this configuration.
     */
    public function getResetTime(): \Illuminate\Support\Carbon
    {
        return now()->addMinutes($this->window_minutes);
    }

    /**
     * Get the block end time for a rate limit based on this configuration.
     */
    public function getBlockEndTime(): \Illuminate\Support\Carbon
    {
        return now()->addMinutes($this->block_duration_minutes);
    }
}
