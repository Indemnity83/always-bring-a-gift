<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'person_id',
        'event_type_id',
        'recurrence',
        'show_milestone',
        'date',
        'target_value',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'target_value' => 'decimal:2',
            'show_milestone' => 'boolean',
        ];
    }

    /**
     * Get the person that owns the event
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Get the event type that owns the event
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * Get the gifts for the event
     */
    public function gifts(): HasMany
    {
        return $this->hasMany(Gift::class);
    }

    /**
     * Get the completions for the event
     */
    public function completions(): HasMany
    {
        return $this->hasMany(EventCompletion::class);
    }

    /**
     * Get gifts for a specific year
     */
    public function giftsForYear(int $year): HasMany
    {
        return $this->gifts()->where('year', $year);
    }

    /**
     * Get the next occurrence date
     */
    protected function nextOccurrence(): Attribute
    {
        return Attribute::make(
            get: function (): Carbon {
                if ($this->recurrence === 'none') {
                    return $this->date;
                }

                $today = now()->startOfDay();
                $currentYear = $today->year;

                $thisYearOccurrence = Carbon::create(
                    $currentYear,
                    $this->date->month,
                    $this->date->day
                );

                if ($thisYearOccurrence->isFuture() || $thisYearOccurrence->isToday()) {
                    return $thisYearOccurrence;
                }

                return Carbon::create(
                    $currentYear + 1,
                    $this->date->month,
                    $this->date->day
                );
            }
        );
    }

    /**
     * Get the year of the next occurrence
     */
    protected function nextOccurrenceYear(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->next_occurrence->year
        );
    }

    /**
     * Check if the event is completed for a specific year
     */
    public function isCompletedForYear(int $year): bool
    {
        return $this->completions()
            ->where('year', $year)
            ->exists();
    }

    /**
     * Get total gifts value for a specific year
     */
    public function totalGiftsValueForYear(int $year): float
    {
        return (float) $this->giftsForYear($year)->sum('value');
    }

    /**
     * Get remaining value for a specific year (can be negative for overage)
     */
    public function remainingValueForYear(int $year): float
    {
        if ($this->target_value === null) {
            return 0;
        }

        return (float) $this->target_value - $this->totalGiftsValueForYear($year);
    }

    /**
     * Mark the event as complete for a specific year
     */
    public function markCompleteForYear(int $year): void
    {
        $this->completions()->firstOrCreate(
            ['year' => $year],
            ['completed_at' => now()]
        );
    }

    /**
     * Unmark the event as complete for a specific year
     */
    public function unmarkCompleteForYear(int $year): void
    {
        $this->completions()
            ->where('year', $year)
            ->delete();
    }

    /**
     * Get the milestone number for the next occurrence
     * (e.g., 38 for a 38th birthday, 5 for a 5th anniversary)
     */
    protected function milestone(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                // Only calculate milestone for yearly recurring events
                if ($this->recurrence !== 'yearly') {
                    return null;
                }

                // Calculate years since the original event date
                return $this->next_occurrence_year - $this->date->year;
            }
        );
    }

    /**
     * Get the display name with milestone number if applicable
     * (e.g., "38th Birthday" instead of just "Birthday")
     */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                // Only show milestone if flag is enabled and milestone is greater than 0
                if ($this->show_milestone && $this->milestone !== null && $this->milestone > 0) {
                    return $this->ordinal($this->milestone).' '.$this->eventType->name;
                }

                return $this->eventType->name;
            }
        );
    }

    /**
     * Convert a number to its ordinal form (1st, 2nd, 3rd, etc.)
     */
    protected function ordinal(int $number): string
    {
        $suffix = 'th';

        if (! in_array($number % 100, [11, 12, 13])) {
            $suffix = match ($number % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };
        }

        return $number.$suffix;
    }
}
