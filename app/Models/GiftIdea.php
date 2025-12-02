<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftIdea extends Model
{
    /** @use HasFactory<\Database\Factories\GiftIdeaFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'person_id',
        'idea',
    ];

    /**
     * Get the person that owns the gift idea
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
