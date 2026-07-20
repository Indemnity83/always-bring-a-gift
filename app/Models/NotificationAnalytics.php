<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationAnalytics extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationAnalyticsFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'channel',
        'notification_type',
        'date',
        'sent_count',
        'delivered_count',
        'failed_count',
        'read_count',
        'click_count',
        'delivery_rate',
        'open_rate',
        'click_rate',
        'avg_delivery_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'sent_count' => 'integer',
            'delivered_count' => 'integer',
            'failed_count' => 'integer',
            'read_count' => 'integer',
            'click_count' => 'integer',
            'delivery_rate' => 'decimal:2',
            'open_rate' => 'decimal:2',
            'click_rate' => 'decimal:2',
            'avg_delivery_time' => 'decimal:2',
        ];
    }

    /**
     * Scope a query to only include analytics for a specific channel.
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope a query to only include analytics for a specific notification type.
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope a query to only include analytics within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include analytics from the last N days.
     */
    public function scopeLastDays($query, int $days)
    {
        return $query->where('date', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to order by date descending.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('date', 'desc');
    }

    /**
     * Scope a query to order by date ascending.
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('date', 'asc');
    }

    /**
     * Get the total number of notifications sent.
     */
    public function getTotalSent(): int
    {
        return $this->sent_count;
    }

    /**
     * Get the total number of notifications delivered.
     */
    public function getTotalDelivered(): int
    {
        return $this->delivered_count;
    }

    /**
     * Get the total number of notifications that failed.
     */
    public function getTotalFailed(): int
    {
        return $this->failed_count;
    }

    /**
     * Get the total number of notifications read.
     */
    public function getTotalRead(): int
    {
        return $this->read_count;
    }

    /**
     * Get the total number of notifications clicked.
     */
    public function getTotalClicked(): int
    {
        return $this->click_count;
    }

    /**
     * Calculate the success rate percentage.
     */
    public function getSuccessRate(): float
    {
        if ($this->sent_count === 0) {
            return 0.0;
        }

        return (($this->sent_count - $this->failed_count) / $this->sent_count) * 100;
    }

    /**
     * Check if the analytics show good performance.
     */
    public function hasGoodPerformance(): bool
    {
        return $this->delivery_rate >= 95.0 && $this->open_rate >= 20.0;
    }

    /**
     * Check if the analytics show poor performance.
     */
    public function hasPoorPerformance(): bool
    {
        return $this->delivery_rate < 90.0 || $this->open_rate < 10.0;
    }

    /**
     * Increment the sent count.
     */
    public function incrementSent(int $count = 1): void
    {
        $this->sent_count += $count;
        $this->recalculateRates();
        $this->save();
    }

    /**
     * Increment the delivered count.
     */
    public function incrementDelivered(int $count = 1): void
    {
        $this->delivered_count += $count;
        $this->recalculateRates();
        $this->save();
    }

    /**
     * Increment the failed count.
     */
    public function incrementFailed(int $count = 1): void
    {
        $this->failed_count += $count;
        $this->recalculateRates();
        $this->save();
    }

    /**
     * Increment the read count.
     */
    public function incrementRead(int $count = 1): void
    {
        $this->read_count += $count;
        $this->recalculateRates();
        $this->save();
    }

    /**
     * Increment the click count.
     */
    public function incrementClick(int $count = 1): void
    {
        $this->click_count += $count;
        $this->recalculateRates();
        $this->save();
    }

    /**
     * Recalculate all rates based on current counts.
     */
    private function recalculateRates(): void
    {
        if ($this->sent_count > 0) {
            $this->delivery_rate = ($this->delivered_count / $this->sent_count) * 100;
            $this->open_rate = ($this->read_count / $this->sent_count) * 100;
            $this->click_rate = ($this->click_count / $this->sent_count) * 100;
        } else {
            $this->delivery_rate = 0.0;
            $this->open_rate = 0.0;
            $this->click_rate = 0.0;
        }
    }
}
