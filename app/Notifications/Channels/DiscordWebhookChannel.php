<?php

namespace App\Notifications\Channels;

use App\Exceptions\WebhookValidationException;
use App\Services\WebhookValidationService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DiscordWebhookChannel
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
        if (! method_exists($notification, 'toDiscord')) {
            Log::channel('notifications')->debug('Discord channel skipped: notification missing toDiscord method', [
                'notification_class' => get_class($notification),
            ]);

            return;
        }

        $webhook = $notifiable->routeNotificationFor('discord', $notification);

        if (! $webhook) {
            Log::channel('notifications')->warning('Discord channel skipped: no webhook URL routed', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => method_exists($notifiable, 'getKey') ? $notifiable->getKey() : 'anonymous',
                'notification_class' => get_class($notification),
            ]);

            return;
        }

        $payload = $notification->toDiscord($notifiable);

        if (empty($payload)) {
            return;
        }

        $startTime = microtime(true);

        try {
            $this->sendWithRetry($webhook, $payload, $notifiable);
        } catch (Throwable $exception) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::warning('Discord webhook notification failed', [
                'error' => $exception->getMessage(),
                'webhook' => $this->sanitizeWebhookUrl($webhook),
                'duration_ms' => $duration,
                'exception_class' => get_class($exception),
                'notifiable_id' => $notifiable->getKey() ?? 'unknown',
            ]);

            // Swallow errors to avoid 500s in UI; failures are logged.
        }
    }

    /**
     * Validate webhook URL.
     */
    protected function validateWebhook(string $webhook): void
    {
        try {
            if (! $this->webhookValidationService->validateWebhookUrl($webhook, 'discord')) {
                throw new WebhookValidationException('Invalid Discord webhook URL');
            }
        } catch (WebhookValidationException $exception) {
            Log::warning('Discord webhook validation failed', [
                'webhook' => $this->sanitizeWebhookUrl($webhook),
                'errors' => $exception->getValidationErrors(),
            ]);

            throw $exception;
        }
    }

    /**
     * Send webhook with retry logic.
     */
    protected function sendWithRetry(string $webhook, array $payload, mixed $notifiable): void
    {
        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'AlwaysBringAGift-DiscordWebhook/1.0',
                    ])
                    ->post($webhook, $payload);

                if ($response->successful()) {
                    Log::info('Discord webhook notification sent successfully', [
                        'webhook' => $this->sanitizeWebhookUrl($webhook),
                        'status_code' => $response->status(),
                        'attempt' => $attempt + 1,
                        'notifiable_id' => $notifiable->getKey() ?? 'unknown',
                    ]);

                    return;
                }

                if ($response->status() === 429) {
                    $retryAfter = (int) $response->header('Retry-After', 60);
                    Log::warning('Discord rate limit hit, retrying', [
                        'webhook' => $this->sanitizeWebhookUrl($webhook),
                        'retry_after' => $retryAfter,
                        'attempt' => $attempt + 1,
                    ]);
                    usleep($retryAfter * 1000000);

                    continue;
                }

                Log::warning('Discord webhook request failed', [
                    'webhook' => $this->sanitizeWebhookUrl($webhook),
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'attempt' => $attempt + 1,
                ]);

                // Retry on server errors, otherwise give up
                if ($response->status() >= 500 && $attempt < $this->maxRetries) {
                    usleep($this->retryDelay * 1000);

                    continue;
                }

                return;
            } catch (Throwable $exception) {
                Log::warning('Discord webhook send failed', [
                    'webhook' => $this->sanitizeWebhookUrl($webhook),
                    'error' => $exception->getMessage(),
                    'attempt' => $attempt + 1,
                ]);

                if ($attempt === $this->maxRetries) {
                    return;
                }

                usleep($this->retryDelay * 1000);

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
