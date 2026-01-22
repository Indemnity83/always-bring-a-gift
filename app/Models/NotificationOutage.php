<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationOutage extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationOutageFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notification_outages';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'channel',
        'outage_type',
        'started_at',
        'ended_at',
        'description',
        'is_resolved',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'is_resolved' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include active outages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope a query to only include resolved outages.
     */
    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    /**
     * Scope a query to only include outages for a specific channel.
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope a query to only include outages of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('outage_type', $type);
    }

    /**
     * Scope a query to only include recent outages.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('started_at', '>=', now()->subDays($days));
    }

    /**
     * Get the duration of the outage.
     */
    public function getDuration(): ?\Illuminate\Support\Carbon
    {
        if (! $this->ended_at) {
            return null;
        }

        return $this->started_at->diff($this->ended_at);
    }

    /**
     * Get the duration in minutes.
     */
    public function getDurationInMinutes(): ?int
    {
        if (! $this->ended_at) {
            return null;
        }

        return $this->started_at->diffInMinutes($this->ended_at);
    }

    /**
     * Check if the outage is currently active.
     */
    public function isActive(): bool
    {
        return ! $this->is_resolved;
    }

    /**
     * Resolve the outage.
     */
    public function resolve(): void
    {
        $this->ended_at = now();
        $this->is_resolved = true;
        $this->save();
    }

    /**
     * Reopen the outage.
     */
    public function reopen(): void
    {
        $this->ended_at = null;
        $this->is_resolved = false;
        $this->save();
    }
}
