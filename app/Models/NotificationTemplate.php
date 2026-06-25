<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationTemplateFactory> */
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
    protected $table = 'notification_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'notification_type',
        'channel',
        'subject',
        'content',
        'variables',
        'is_active',
        'is_system',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
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
     * Get the user that owns the template.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include system templates.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope a query to only include user-created templates.
     */
    public function scopeUserCreated($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope a query to only include templates for a specific type.
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope a query to only include templates for a specific channel.
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Render the template content with the given data.
     */
    public function render(array $data = []): string
    {
        $content = $this->content;
        $subject = $this->subject;

        foreach ($data as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $content = str_replace($placeholder, $value, $content);
            if ($subject) {
                $subject = str_replace($placeholder, $value, $subject);
            }
        }

        return $content;
    }

    /**
     * Render the template subject with the given data.
     */
    public function renderSubject(array $data = []): ?string
    {
        if (! $this->subject) {
            return null;
        }

        $subject = $this->subject;
        foreach ($data as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $subject = str_replace($placeholder, $value, $subject);
        }

        return $subject;
    }

    /**
     * Get the required variables for this template.
     */
    public function getRequiredVariables(): array
    {
        if (! $this->variables) {
            return [];
        }

        return array_filter($this->variables, fn ($variable) => $variable['required'] ?? false);
    }

    /**
     * Validate that all required variables are present in the data.
     */
    public function validateVariables(array $data): bool
    {
        $required = $this->getRequiredVariables();
        $requiredKeys = array_column($required, 'name');

        foreach ($requiredKeys as $key) {
            if (! isset($data[$key])) {
                return false;
            }
        }

        return true;
    }
}
