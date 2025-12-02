<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventCompletion extends Model
{
    /** @use HasFactory<\Database\Factories\EventCompletionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'year',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the event that owns the completion
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
