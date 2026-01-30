<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    /** @use HasFactory<\Database\Factories\PersonFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'profile_picture',
        'birthday',
        'anniversary',
        'christmas_default_date',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'anniversary' => 'date',
        ];
    }

    /**
     * Get the events for the person
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the user that owns the person.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the gift ideas for the person
     */
    public function giftIdeas(): HasMany
    {
        return $this->hasMany(GiftIdea::class);
    }

    public function getChristmasDateForYear(int $year): string
    {
        $monthDay = $this->christmas_default_date
            ?: $this->user?->getChristmasDefaultDate()
            ?: config('reminders.christmas_default_date', '12-25');

        return sprintf('%04d-%s', $year, $monthDay);
    }
}
