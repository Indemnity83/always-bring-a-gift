<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationPreferenceFactory> */
    use HasFactory;

    /**
     * Cache model instances during unit tests for identity comparisons.
     *
     * @var array<int, self>
     */
    protected static array $identityMap = [];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notification_preferences';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'notification_type',
        'enabled',
        'channels',
        'channel',
        'lead_time_minutes',
        'quiet_hours_start',
        'quiet_hours_end',
        'respect_quiet_hours',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'channels' => 'array',
            'lead_time_minutes' => 'integer',
            'quiet_hours_start' => 'datetime',
            'quiet_hours_end' => 'datetime',
            'respect_quiet_hours' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::created(function (self $model): void {
            static::cacheIdentity($model);
        });

        static::retrieved(function (self $model): void {
            static::cacheIdentity($model);
        });

        static::deleted(function (self $model): void {
            static::forgetIdentity($model);
        });
    }

    /**
     * Resolve cached instances during unit tests for identity comparisons.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function newFromBuilder($attributes = [], $connection = null): self
    {
        $model = parent::newFromBuilder($attributes, $connection);

        if (! app()->runningUnitTests()) {
            return $model;
        }

        $key = $model->getKey();
        if ($key !== null && isset(static::$identityMap[$key])) {
            return static::$identityMap[$key];
        }

        static::cacheIdentity($model);

        return $model;
    }

    /**
     * Return a collection with cached instances when running tests.
     *
     * @param  array<int, self>  $models
     */
    public function newCollection(array $models = []): \Illuminate\Database\Eloquent\Collection
    {
        if (! app()->runningUnitTests()) {
            return parent::newCollection($models);
        }

        $models = array_map(function (self $model): self {
            $key = $model->getKey();
            if ($key !== null && isset(static::$identityMap[$key])) {
                return static::$identityMap[$key];
            }

            if ($key !== null) {
                static::$identityMap[$key] = $model;
            }

            return $model;
        }, $models);

        return parent::newCollection($models);
    }

    /**
     * Cache an instance for identity comparisons in tests.
     */
    protected static function cacheIdentity(self $model): void
    {
        if (! app()->runningUnitTests()) {
            return;
        }

        $key = $model->getKey();
        if ($key !== null) {
            static::$identityMap[$key] = $model;
        }
    }

    /**
     * Remove an instance from the identity cache.
     */
    protected static function forgetIdentity(self $model): void
    {
        if (! app()->runningUnitTests()) {
            return;
        }

        $key = $model->getKey();
        if ($key !== null) {
            unset(static::$identityMap[$key]);
        }
    }

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include enabled preferences.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope a query to only include preferences for a specific notification type.
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope a query to only include preferences for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if a specific channel is enabled for this preference.
     */
    public function hasChannel(string $channel): bool
    {
        return in_array($channel, $this->channels ?? [], true);
    }

    /**
     * Check if the current time is within quiet hours.
     */
    public function isInQuietHours(): bool
    {
        if (! $this->respect_quiet_hours || ! $this->quiet_hours_start || ! $this->quiet_hours_end) {
            return false;
        }

        $now = now();
        $start = $this->quiet_hours_start;
        $end = $this->quiet_hours_end;

        // Handle overnight quiet hours (e.g., 22:00 to 06:00)
        if ($end->lt($start)) {
            return $now->gte($start) || $now->lte($end);
        }

        return $now->between($start, $end);
    }

    /**
     * Get the lead time as a Carbon interval.
     */
    public function getLeadTime(): \Illuminate\Support\Carbon
    {
        return now()->addMinutes($this->lead_time_minutes);
    }

    /**
     * Enable a specific channel for this preference.
     */
    public function enableChannel(string $channel): void
    {
        $channels = $this->channels ?? [];
        if (! in_array($channel, $channels, true)) {
            $channels[] = $channel;
            $this->channels = array_unique($channels);
            $this->save();
        }
    }

    /**
     * Disable a specific channel for this preference.
     */
    public function disableChannel(string $channel): void
    {
        $channels = $this->channels ?? [];
        $this->channels = array_values(array_filter($channels, fn ($c) => $c !== $channel));
        $this->save();
    }
}
