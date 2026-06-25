<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationMetric extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationMetricFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notification_metrics';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'channel',
        'date',
        'sent_count',
        'failed_count',
        'success_rate',
        'avg_response_time',
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
            'failed_count' => 'integer',
            'success_rate' => 'decimal:2',
            'avg_response_time' => 'decimal:2',
        ];
    }

    /**
     * Scope a query to only include metrics for a specific channel.
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope a query to only include metrics within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include metrics from the last N days.
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
     * Get the total number of notifications that failed.
     */
    public function getTotalFailed(): int
    {
        return $this->failed_count;
    }

    /**
     * Get the success rate percentage.
     */
    public function getSuccessRate(): float
    {
        return $this->success_rate;
    }

    /**
     * Check if the metrics show good performance.
     */
    public function hasGoodPerformance(): bool
    {
        return $this->success_rate >= 95.0;
    }

    /**
     * Check if the metrics show poor performance.
     */
    public function hasPoorPerformance(): bool
    {
        return $this->success_rate < 90.0;
    }

    /**
     * Increment the sent count.
     */
    public function incrementSent(int $count = 1): void
    {
        $this->sent_count += $count;
        $this->recalculateSuccessRate();
        $this->save();
    }

    /**
     * Increment the failed count.
     */
    public function incrementFailed(int $count = 1): void
    {
        $this->failed_count += $count;
        $this->recalculateSuccessRate();
        $this->save();
    }

    /**
     * Recalculate the success rate based on current counts.
     */
    private function recalculateSuccessRate(): void
    {
        if ($this->sent_count > 0) {
            $this->success_rate = (($this->sent_count - $this->failed_count) / $this->sent_count) * 100;
        } else {
            $this->success_rate = 0.0;
        }
    }
}
