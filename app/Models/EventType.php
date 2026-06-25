<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventType extends Model
{
    public const CHRISTMAS_NAME = 'Christmas';

    /** @use HasFactory<\Database\Factories\EventTypeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'is_custom',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_custom' => 'boolean',
        ];
    }

    /**
     * Get the events for the event type
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Scope a query to only include predefined event types
     */
    public function scopePredefined(Builder $query): void
    {
        $query->where('is_custom', false);
    }

    /**
     * Scope a query to only include custom event types
     */
    public function scopeCustom(Builder $query): void
    {
        $query->where('is_custom', true);
    }
}
