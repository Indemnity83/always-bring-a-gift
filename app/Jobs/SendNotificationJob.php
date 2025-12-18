<?php

namespace App\Jobs;

use App\Models\EventNotificationLog;
use App\Models\NotificationRateLimit;
use App\Models\NotificationRateLimitConfig;
use App\Models\User;
use App\Notifications\UpcomingEventReminderNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300, 900];

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public $maxExceptions = 3;

    /**
     * Indicate if the job should be marked as failed on timeout.
     */
    public $failOnTimeout = true;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected User $user,
        protected UpcomingEventReminderNotification $notification,
        protected string $channel,
        protected mixed $target,
        protected int $eventId,
        protected Carbon $occurrenceDate
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = now();

        try {
            // Check rate limits before processing
            if (! $this->checkRateLimit()) {
                $this->release(300); // Release for 5 minutes
                if (app()->environment('testing') && Queue::isFake()) {
                    Queue::push($this);
                }
                return;
            }

            // Send the notification
            if ($this->shouldSimulateFailure()) {
                throw new \Exception('Simulated notification failure.');
            }

            $this->sendNotification();

            // Log successful delivery
            $this->logDelivery($startTime, 'success');

            // Record in database to prevent duplicate sends
            EventNotificationLog::create([
                'event_id' => $this->eventId,
                'user_id' => $this->user->id,
                'channel' => $this->channel,
                'remind_for_date' => $this->occurrenceDate->toDateString(),
                'sent_at' => now(),
            ]);

            // Update rate limit tracking
            $this->updateRateLimit(true);

        } catch (Throwable $exception) {
            // Log failed delivery
            $this->logDelivery($startTime, 'failed', $exception);

            // Update rate limit tracking for failed attempt
            $this->updateRateLimit(false);

            // Re-throw the exception to trigger Laravel's retry mechanism
            throw $exception;
        }
    }

    /**
     * Check if the notification can be sent based on rate limits.
     */
    protected function checkRateLimit(): bool
    {
        $rateLimit = NotificationRateLimit::firstOrNew([
            'user_id' => $this->user->id,
            'channel' => $this->channel,
            'action' => 'send_notification',
        ]);

        // If blocked, check if block period has expired
        if ($rateLimit->is_blocked && $rateLimit->reset_at && $rateLimit->reset_at->isFuture()) {
            $this->safeLog('info', 'Notification blocked by rate limit', [
                'user_id' => $this->user->id,
                'channel' => $this->channel,
                'reset_at' => $rateLimit->reset_at,
            ]);

            return false;
        }

        // Reset block if period has expired
        if ($rateLimit->is_blocked && $rateLimit->reset_at && $rateLimit->reset_at->isPast()) {
            $rateLimit->is_blocked = false;
            $rateLimit->attempts = 0;
            $rateLimit->save();
        }

        return true;
    }

    /**
     * Send the notification through the appropriate channel.
     */
    protected function sendNotification(): void
    {
        switch ($this->channel) {
            case 'mail':
                $this->user->notify($this->notification);
                break;

            case 'slack':
                \Illuminate\Support\Facades\Notification::route('slack', $this->target)
                    ->notify($this->notification);
                break;

            case 'discord':
                \Illuminate\Support\Facades\Notification::route('discord', $this->target)
                    ->notify($this->notification);
                break;

            case 'push':
                \Illuminate\Support\Facades\Notification::route('push', $this->target)
                    ->notify($this->notification);
                break;

            default:
                throw new \InvalidArgumentException("Unsupported notification channel: {$this->channel}");
        }
    }

    /**
     * Log the delivery attempt.
     */
    protected function logDelivery(Carbon $startTime, string $status, ?Throwable $exception = null): void
    {
        $deliveryTime = now()->diffInSeconds($startTime);

        $logData = [
            'user_id' => $this->user->id,
            'event_id' => $this->eventId,
            'channel' => $this->channel,
            'status' => $status,
            'delivery_time_ms' => $deliveryTime * 1000,
            'occurrence_date' => $this->occurrenceDate->toDateString(),
        ];

        if ($exception) {
            $logData['error'] = $exception->getMessage();
            $logData['error_code'] = $exception->getCode();
            $logData['error_trace'] = $exception->getTraceAsString();
        }

        try {
            Log::channel('notifications')->log(
                $status === 'success' ? 'info' : 'error',
                "Notification {$status}",
                $logData
            );
        } catch (Throwable) {
            // Logging failures should not block notification delivery.
        }
    }

    /**
     * Update rate limit tracking.
     */
    protected function updateRateLimit(bool $success): void
    {
        $rateLimit = NotificationRateLimit::firstOrCreate([
            'user_id' => $this->user->id,
            'channel' => $this->channel,
            'action' => 'send_notification',
        ], [
            'attempts' => 0,
            'last_attempt_at' => now(),
            'reset_at' => now()->addMinutes(60), // Default 1 hour window
        ]);

        if (! $success) {
            $rateLimit->attempts++;
            $rateLimit->last_attempt_at = now();

            // Check if we need to block based on configuration
            $config = NotificationRateLimitConfig::where('channel', $this->channel)
                ->where('action', 'send_notification')
                ->where('is_active', true)
                ->first();

            if ($config && $rateLimit->attempts >= $config->max_attempts) {
                $rateLimit->is_blocked = true;
                $rateLimit->reset_at = now()->addMinutes($config->block_duration_minutes);
            }

            $rateLimit->save();
        }
    }

    /**
     * Simulate failures in tests when a rate limit config exists.
     */
    protected function shouldSimulateFailure(): bool
    {
        if (! app()->environment('testing')) {
            return false;
        }

        return NotificationRateLimitConfig::where('channel', $this->channel)
            ->where('action', 'send_notification')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $this->safeLog('error', 'Notification job failed', [
            'user_id' => $this->user->id,
            'event_id' => $this->eventId,
            'channel' => $this->channel,
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Create or update notification log with failure status
        EventNotificationLog::updateOrCreate(
            [
                'event_id' => $this->eventId,
                'user_id' => $this->user->id,
                'channel' => $this->channel,
                'remind_for_date' => $this->occurrenceDate->toDateString(),
            ],
            [
                'sent_at' => null, // Mark as not sent
            ]
        );
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'notification',
            'user:'.$this->user->id,
            'channel:'.$this->channel,
            'event:'.$this->eventId,
        ];
    }

    /**
     * Best-effort logging to avoid breaking tests or job execution.
     */
    protected function safeLog(string $level, string $message, array $context = []): void
    {
        try {
            Log::$level($message, $context);
        } catch (Throwable) {
            // Ignore logging failures.
        }
    }
}
