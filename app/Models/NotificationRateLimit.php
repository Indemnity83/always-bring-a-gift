<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRateLimit extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationRateLimitFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'channel',
        'action',
        'attempts',
        'last_attempt_at',
        'reset_at',
        'is_blocked',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'last_attempt_at' => 'datetime',
            'reset_at' => 'datetime',
            'is_blocked' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the rate limit.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the configuration for this rate limit.
     */
    public function config(): BelongsTo
    {
        return $this->belongsTo(NotificationRateLimitConfig::class, 'channel', 'channel');
    }

    /**
     * Resolve the configuration for the current action.
     */
    public function configForAction(): ?NotificationRateLimitConfig
    {
        if ($this->action === null) {
            return null;
        }

        return NotificationRateLimitConfig::where('channel', $this->channel)
            ->where('action', $this->action)
            ->first();
    }

    /**
     * Check if the rate limit has expired.
     */
    public function isExpired(): bool
    {
        if (! $this->reset_at) {
            return false;
        }

        return now()->isAfter($this->reset_at);
    }

    /**
     * Increment the attempt count and update timestamps.
     */
    public function incrementAttempt(): void
    {
        $this->attempts++;
        $this->last_attempt_at = now();
        $this->save();
    }

    /**
     * Reset the rate limit.
     */
    public function reset(): void
    {
        $this->attempts = 0;
        $this->last_attempt_at = null;
        $this->is_blocked = false;
        $this->save();
    }

    /**
     * Block the rate limit.
     */
    public function block(): void
    {
        $this->is_blocked = true;
        $this->save();
    }
}
