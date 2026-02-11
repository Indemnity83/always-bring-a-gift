<?php

namespace App\Jobs;

use App\Models\EventNotificationLog;
use App\Models\NotificationRateLimit;
use App\Models\User;
use App\Notifications\UpcomingEventReminderNotification;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class BatchSendNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [300, 900];

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public $maxExceptions = 2;

    /**
     * Indicate if the job should be marked as failed on timeout.
     */
    public $failOnTimeout = true;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 600; // 10 minutes for batch processing

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Collection $notifications,
        protected ?int $batchSize = 50
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = now();
        $totalNotifications = $this->notifications->count();
        $processedCount = 0;
        $successCount = 0;
        $failureCount = 0;
        $rateLimitedCount = 0;

        $this->safeLog('info', 'Starting batch notification processing', [
            'total_notifications' => $totalNotifications,
            'batch_size' => $this->batchSize,
        ]);

        // Process notifications in batches to avoid memory issues
        $this->notifications->chunk($this->batchSize)->each(function ($batch) use (
            &$processedCount,
            &$successCount,
            &$failureCount,
            &$rateLimitedCount
        ) {
            $batch->each(function ($notificationData) use (
                &$processedCount,
                &$successCount,
                &$failureCount,
                &$rateLimitedCount
            ) {
                $processedCount++;

                try {
                    $result = $this->processSingleNotification($notificationData);

                    match ($result) {
                        'success' => $successCount++,
                        'rate_limited' => $rateLimitedCount++,
                        'failed' => $failureCount++,
                    };
                } catch (Throwable $exception) {
                    $failureCount++;
                    $this->safeLog('error', 'Failed to process notification in batch', [
                        'user_id' => $notificationData['user_id'],
                        'event_id' => $notificationData['event_id'],
                        'channel' => $notificationData['channel'],
                        'error' => $exception->getMessage(),
                    ]);
                }
            });
        });

        $processingTime = now()->diffInSeconds($startTime);

        $this->safeLog('debug', 'Batch notification processing completed', [
            'total_processed' => $processedCount,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'rate_limited_count' => $rateLimitedCount,
            'processing_time_seconds' => $processingTime,
            'average_time_per_notification' => $processedCount > 0 ? round($processingTime / $processedCount, 2) : 0,
        ]);
    }

    /**
     * Process a single notification.
     */
    protected function processSingleNotification(array $notificationData): string
    {
        $user = User::find($notificationData['user_id']);
        if (! $user) {
            $this->safeLog('warning', 'User not found for notification', [
                'user_id' => $notificationData['user_id'],
                'event_id' => $notificationData['event_id'],
            ]);

            return 'failed';
        }

        // Check if notification already sent
        if ($this->alreadySent($notificationData)) {
            return 'success'; // Already processed, count as success
        }

        // Check rate limits
        if (! $this->checkRateLimit($user, $notificationData['channel'])) {
            return 'rate_limited';
        }

        // Create notification instance
        $notification = new UpcomingEventReminderNotification(
            $notificationData['event'],
            $notificationData['occurrence_date'],
            $user,
            $notificationData['channel'],
            $notificationData['days_away']
        );

        // Dispatch individual notification job
        SendNotificationJob::dispatch(
            $user,
            $notification,
            $notificationData['channel'],
            $notificationData['target'],
            $notificationData['event_id'],
            $notificationData['occurrence_date']
        );

        return 'success';
    }

    /**
     * Check if notification has already been sent.
     */
    protected function alreadySent(array $notificationData): bool
    {
        return EventNotificationLog::where('user_id', $notificationData['user_id'])
            ->where('event_id', $notificationData['event_id'])
            ->where('channel', $notificationData['channel'])
            ->whereDate('remind_for_date', $notificationData['occurrence_date'])
            ->whereNotNull('sent_at')
            ->exists();
    }

    /**
     * Check rate limits for a user and channel.
     */
    protected function checkRateLimit(User $user, string $channel): bool
    {
        $rateLimit = NotificationRateLimit::firstOrNew([
            'user_id' => $user->id,
            'channel' => $channel,
            'action' => 'send_notification',
        ]);

        // If blocked, check if block period has expired
        if ($rateLimit->is_blocked && $rateLimit->reset_at && $rateLimit->reset_at->isFuture()) {
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
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $this->safeLog('error', 'Batch notification job failed', [
            'total_notifications' => $this->notifications->count(),
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'batch_notification',
            'count:'.$this->notifications->count(),
        ];
    }

    /**
     * Best-effort logging to avoid breaking batch jobs.
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
