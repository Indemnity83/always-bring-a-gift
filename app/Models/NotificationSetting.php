<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

class NotificationSetting extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationSettingFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'lead_time_days',
        'remind_at',
        'channels',
        'slack_webhook_url',
        'discord_webhook_url',
        'push_endpoint',
        'push_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channels' => 'array',
        ];
    }

    /**
     * Get the user the settings belong to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve which channels are enabled, falling back to config defaults.
     */
    protected function resolvedChannels(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $channels = $this->channels ?? config('reminders.default_channels', []);

                return collect($channels)
                    ->filter()
                    ->map(fn ($channel) => strtolower((string) $channel))
                    ->unique()
                    ->filter(function (string $channel): bool {
                        return match ($channel) {
                            'slack' => $this->slackWebhook() !== null,
                            'discord' => $this->discordWebhook() !== null,
                            'push' => $this->pushEndpoint() !== null && $this->pushToken() !== null,
                            default => true,
                        };
                    })
                    ->values()
                    ->all();
            }
        );
    }

    /**
     * Get the preferred reminder time in HH:MM format.
     */
    protected function reminderTime(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $value = $this->remind_at ?: config('reminders.send_time');

                return substr($value, 0, 5);
            }
        );
    }

    /**
     * Get the Slack webhook URL for the user or fallback configuration.
     */
    public function slackWebhook(): ?string
    {
        return $this->normalizeOptionalString($this->slack_webhook_url);
    }

    /**
     * Get the Discord webhook URL for the user or fallback configuration.
     */
    public function discordWebhook(): ?string
    {
        return $this->normalizeOptionalString($this->discord_webhook_url);
    }

    /**
     * Get the push endpoint for the user or fallback configuration.
     */
    public function pushEndpoint(): ?string
    {
        return $this->normalizeOptionalString($this->push_endpoint);
    }

    /**
     * Get the push token for the user or fallback configuration.
     */
    public function pushToken(): ?string
    {
        return $this->normalizeOptionalString($this->push_token);
    }

    /**
     * Create default settings for a user if they do not exist.
     */
    public static function forUser(User $user, bool $create = false): ?self
    {
        if ($user->relationLoaded('notificationSetting')) {
            $settings = $user->getRelation('notificationSetting');

            if ($settings instanceof self) {
                return $settings;
            }

            if (! $create) {
                return null;
            }
        }

        if (! $create) {
            return $user->notificationSetting()->first();
        }

        return $user->notificationSetting()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'lead_time_days' => config('reminders.lead_time_days'),
                'remind_at' => self::normalizeTime(config('reminders.send_time')),
                'channels' => config('reminders.default_channels'),
            ]
        );
    }

    /**
     * Utility helper to check if a channel is enabled.
     */
    public function hasChannel(string $channel): bool
    {
        return in_array($channel, Arr::wrap($this->resolved_channels), true);
    }

    /**
     * Normalize reminder time to HH:MM:SS.
     */
    protected static function normalizeTime(string $value): string
    {
        if (strlen($value) === 5) {
            return $value.':00';
        }

        return $value;
    }

    /**
     * Normalize optional string values to null when empty.
     */
    protected function normalizeOptionalString(?string $value): ?string
    {
        $trimmed = $value !== null ? trim($value) : null;

        return $trimmed === '' ? null : $trimmed;
    }
}
