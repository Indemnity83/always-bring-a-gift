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

class SlackWebhookChannel
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
        if (! method_exists($notification, 'toSlack')) {
            Log::channel('notifications')->debug('Slack channel skipped: notification missing toSlack method', [
                'notification_class' => get_class($notification),
            ]);

            return;
        }

        $webhook = $notifiable->routeNotificationFor('slack', $notification);

        if (! $webhook) {
            Log::channel('notifications')->warning('Slack channel skipped: no webhook URL routed', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => method_exists($notifiable, 'getKey') ? $notifiable->getKey() : 'anonymous',
                'notification_class' => get_class($notification),
            ]);

            return;
        }

        $payload = $notification->toSlack($notifiable);

        if (empty($payload)) {
            return;
        }

        // Normalize plain text payloads
        if (is_string($payload)) {
            $payload = ['text' => $payload];
        }

        $startTime = microtime(true);
        $attempt = 0;

        try {
            // Send with retry logic
            $this->sendWithRetry($webhook, $payload, $attempt, $notifiable);
        } catch (Throwable $exception) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Slack webhook notification failed', [
                'error' => $exception->getMessage(),
                'webhook' => $this->sanitizeWebhookUrl($webhook),
                'duration_ms' => $duration,
                'attempt' => $attempt + 1,
                'exception_class' => get_class($exception),
                'notifiable_id' => $notifiable->getKey() ?? 'unknown',
            ]);

            throw new NotificationDeliveryException(
                'Failed to send Slack notification',
                'slack',
                $this->sanitizeWebhookUrl($webhook),
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
    protected function validateWebhook(string $webhook): void
    {
        try {
            if (! $this->webhookValidationService->validateWebhookUrl($webhook, 'slack')) {
                throw new WebhookValidationException('Invalid Slack webhook URL');
            }
        } catch (WebhookValidationException $exception) {
            Log::warning('Slack webhook validation failed', [
                'webhook' => $this->sanitizeWebhookUrl($webhook),
                'errors' => $exception->getValidationErrors(),
            ]);

            throw $exception;
        }
    }

    /**
     * Send webhook with retry logic.
     */
    protected function sendWithRetry(string $webhook, array $payload, int $attempt, mixed $notifiable): void
    {
        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'AlwaysBringAGift-SlackWebhook/1.0',
                    ])
                    ->post($webhook, $payload);

                if ($response->successful()) {
                    Log::info('Slack webhook notification sent successfully', [
                        'webhook' => $this->sanitizeWebhookUrl($webhook),
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
                        'Slack rate limit exceeded',
                        'slack_webhook',
                        (int) $retryAfter,
                        0,
                        0,
                        429,
                        null
                    );
                }

                // Handle other HTTP errors
                throw new WebhookSendException(
                    'Slack webhook request failed',
                    $response->status(),
                    $response->body(),
                    [
                        'webhook' => $this->sanitizeWebhookUrl($webhook),
                        'attempt' => $attempt + 1,
                    ]
                );
            } catch (RateLimitExceededException $exception) {
                if ($attempt === $this->maxRetries) {
                    throw $exception;
                }

                $delayMs = $exception->getRetryAfter() * 1000;
                Log::info('Slack rate limit hit, retrying', [
                    'webhook' => $this->sanitizeWebhookUrl($webhook),
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

                Log::warning('Slack webhook send failed, retrying', [
                    'webhook' => $this->sanitizeWebhookUrl($webhook),
                    'status_code' => $exception->getResponseCode(),
                    'attempt' => $attempt + 1,
                ]);

                usleep($this->retryDelay * 1000); // Convert to microseconds

                continue;
            } catch (Throwable $exception) {
                if ($attempt === $this->maxRetries) {
                    throw new WebhookSendException(
                        'Slack webhook request failed: '.$exception->getMessage(),
                        0,
                        '',
                        [
                            'webhook' => $this->sanitizeWebhookUrl($webhook),
                            'attempt' => $attempt + 1,
                        ],
                        0,
                        $exception
                    );
                }

                Log::warning('Slack webhook send failed, retrying', [
                    'webhook' => $this->sanitizeWebhookUrl($webhook),
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
    protected function sanitizeWebhookUrl(string $webhook): string
    {
        return $this->webhookValidationService->sanitizeWebhookUrl($webhook);
    }
}
