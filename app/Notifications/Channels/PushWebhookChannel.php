<?php

namespace App\Notifications\Channels;

use App\Exceptions\NotificationDeliveryException;
use App\Exceptions\RateLimitExceededException;
use App\Exceptions\WebhookSendException;
use App\Exceptions\WebhookValidationException;
use App\Services\WebhookValidationService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushWebhookChannel
{
    protected WebhookValidationService $webhookValidationService;

    protected int $maxRetries = 3;

    protected int $retryDelay = 1000; // milliseconds

    public function __construct(WebhookValidationService $webhookValidationService)
    {
        $this->webhookValidationService = $webhookValidationService;
    }

    /**
     * Send the given notification.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toPush')) {
            Log::channel('notifications')->debug('Push channel skipped: notification missing toPush method', [
                'notification_class' => get_class($notification),
            ]);

            return;
        }

        $target = $notifiable->routeNotificationFor('push', $notification);

        if (! $target || empty($target['endpoint'])) {
            Log::channel('notifications')->warning('Push channel skipped: no endpoint routed', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => method_exists($notifiable, 'getKey') ? $notifiable->getKey() : 'anonymous',
                'notification_class' => get_class($notification),
                'target_provided' => $target !== null,
            ]);

            return;
        }

        $payload = $notification->toPush($notifiable);

        if (empty($payload)) {
            return;
        }

        $startTime = microtime(true);
        $attempt = 0;

        try {
            // Send with retry logic
            $this->sendWithRetry($target, $payload, $attempt, $notifiable);
        } catch (Throwable $exception) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Push webhook notification failed', [
                'error' => $exception->getMessage(),
                'endpoint' => $this->sanitizeWebhookUrl($target['endpoint']),
                'duration_ms' => $duration,
                'attempt' => $attempt + 1,
                'exception_class' => get_class($exception),
                'notifiable_id' => $notifiable->getKey() ?? 'unknown',
            ]);

            throw new NotificationDeliveryException(
                'Failed to send Push notification',
                'push',
                $this->sanitizeWebhookUrl($target['endpoint']),
                null,
                [
                    'duration_ms' => $duration,
                    'attempt' => $attempt + 1,
                    'exception' => $exception->getMessage(),
                ],
                0,
                $exception
            );
        }
    }

    /**
     * Validate webhook URL.
     */
    protected function validateWebhook(string $endpoint): void
    {
        try {
            if (! $this->webhookValidationService->validateWebhookUrl($endpoint, 'push')) {
                throw new WebhookValidationException('Invalid Push webhook URL');
            }
        } catch (WebhookValidationException $exception) {
            Log::warning('Push webhook validation failed', [
                'endpoint' => $this->sanitizeWebhookUrl($endpoint),
                'errors' => $exception->getValidationErrors(),
            ]);

            throw $exception;
        }
    }

    /**
     * Send webhook with retry logic.
     */
    protected function sendWithRetry(array $target, array $payload, int $attempt, mixed $notifiable): void
    {
        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $request = Http::timeout(30)
                    ->asJson()
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'AlwaysBringAGift-PushWebhook/1.0',
                    ]);

                if (! empty($target['token'])) {
                    $request = $request->withToken($target['token']);
                }

                $response = $request->post($target['endpoint'], $payload);

                if ($response->successful()) {
                    Log::info('Push webhook notification sent successfully', [
                        'endpoint' => $this->sanitizeWebhookUrl($target['endpoint']),
                        'status_code' => $response->status(),
                        'attempt' => $attempt + 1,
                        'notifiable_id' => $notifiable->getKey() ?? 'unknown',
                    ]);

                    return;
                }

                // Handle rate limiting
                if ($response->status() === 429) {
                    $retryAfter = $response->header('Retry-After', 60);
                    throw new RateLimitExceededException(
                        'Push rate limit exceeded',
                        'push_webhook',
                        (int) $retryAfter,
                        0,
                        0,
                        429,
                        null
                    );
                }

                // Handle other HTTP errors
                throw new WebhookSendException(
                    'Push webhook request failed',
                    $response->status(),
                    $response->body(),
                    [
                        'endpoint' => $this->sanitizeWebhookUrl($target['endpoint']),
                        'attempt' => $attempt + 1,
                    ]
                );
            } catch (RateLimitExceededException $exception) {
                if ($attempt === $this->maxRetries) {
                    throw $exception;
                }

                $delayMs = $exception->getRetryAfter() * 1000;
                Log::info('Push rate limit hit, retrying', [
                    'endpoint' => $this->sanitizeWebhookUrl($target['endpoint']),
                    'retry_after' => $exception->getRetryAfter(),
                    'attempt' => $attempt + 1,
                ]);

                usleep($delayMs * 1000); // Convert to microseconds

                continue;
            } catch (WebhookSendException $exception) {
                // Don't retry on client errors (4xx)
                if ($exception->getResponseCode() >= 400 && $exception->getResponseCode() < 500) {
                    throw $exception;
                }

                if ($attempt === $this->maxRetries) {
                    throw $exception;
                }

                Log::warning('Push webhook send failed, retrying', [
                    'endpoint' => $this->sanitizeWebhookUrl($target['endpoint']),
                    'status_code' => $exception->getResponseCode(),
                    'attempt' => $attempt + 1,
                ]);

                usleep($this->retryDelay * 1000); // Convert to microseconds

                continue;
            } catch (Throwable $exception) {
                if ($attempt === $this->maxRetries) {
                    throw new WebhookSendException(
                        'Push webhook request failed: '.$exception->getMessage(),
                        0,
                        '',
                        [
                            'endpoint' => $this->sanitizeWebhookUrl($target['endpoint']),
                            'attempt' => $attempt + 1,
                        ],
                        0,
                        $exception
                    );
                }

                Log::warning('Push webhook send failed, retrying', [
                    'endpoint' => $this->sanitizeWebhookUrl($target['endpoint']),
                    'error' => $exception->getMessage(),
                    'attempt' => $attempt + 1,
                ]);

                usleep($this->retryDelay * 1000); // Convert to microseconds

                continue;
            }
        }
    }

    /**
     * Sanitize webhook URL for logging.
     */
    protected function sanitizeWebhookUrl(string $endpoint): string
    {
        return $this->webhookValidationService->sanitizeWebhookUrl($endpoint);
    }
}
