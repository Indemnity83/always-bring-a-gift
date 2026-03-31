<?php

use App\Exceptions\NotificationDeliveryException;
use App\Exceptions\RateLimitExceededException;
use App\Exceptions\WebhookSendException;
use App\Exceptions\WebhookValidationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotificationDeliveryException $e) {
            return response()->json([
                'message' => 'Notification delivery failed',
                'error' => $e->getMessage(),
                'channel' => $e->getChannel(),
                'recipient' => $e->getRecipient(),
            ], 500);
        });

        $exceptions->render(function (WebhookSendException $e) {
            return response()->json([
                'message' => 'Webhook delivery failed',
                'error' => $e->getMessage(),
                'response_code' => $e->getResponseCode(),
                'response_body' => $e->getResponseBody(),
            ], 500);
        });

        $exceptions->render(function (RateLimitExceededException $e) {
            return response()->json([
                'message' => 'Rate limit exceeded',
                'error' => $e->getMessage(),
                'limit_type' => $e->getLimitType(),
                'retry_after' => $e->getRetryAfter(),
                'current_count' => $e->getCurrentCount(),
                'max_allowed' => $e->getMaxAllowed(),
            ], 429);
        });

        $exceptions->render(function (WebhookValidationException $e) {
            return response()->json([
                'message' => 'Webhook validation failed',
                'error' => $e->getMessage(),
                'validation_errors' => $e->getValidationErrors(),
            ], 400);
        });
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Run frequently; per-user send time is enforced inside ReminderService.
        $schedule->command('reminders:send')->everyMinute();
    })
    ->create();
