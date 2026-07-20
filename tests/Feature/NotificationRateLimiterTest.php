<?php

use App\Models\NotificationRateLimit;
use App\Models\NotificationRateLimitConfig;
use App\Models\User;
use App\Services\NotificationRateLimiter;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

afterEach(function () {
    Cache::flush();
});

test('notification rate limiter allows sending when under limit', function () {
    $user = User::factory()->create();
    $limiter = new NotificationRateLimiter;

    config([
        'notifications.rate_limits.mail.limit' => 5,
        'notifications.rate_limits.mail.window' => 60,
    ]);

    $result = $limiter->canSendNotification($user, 'mail');

    expect($result)->toBeTrue();
});

test('notification rate limiter blocks when limit exceeded', function () {
    $user = User::factory()->create();
    $limiter = new NotificationRateLimiter;

    config([
        'notifications.rate_limits.mail.limit' => 1,
        'notifications.rate_limits.mail.window' => 60,
    ]);

    // First request should succeed
    $result1 = $limiter->canSendNotification($user, 'mail');
    expect($result1)->toBeTrue();

    // Second request should be blocked
    $result2 = $limiter->canSendNotification($user, 'mail');
    expect($result2)->toBeFalse();
});

test('notification rate limiter tracks current count correctly', function () {
    $user = User::factory()->create();
    $limiter = new NotificationRateLimiter;

    config([
        'notifications.rate_limits.mail.limit' => 3,
        'notifications.rate_limits.mail.window' => 60,
    ]);

    // Initial count should be 0
    expect($limiter->getCurrentCount($user, 'mail'))->toBe(0);

    // After one request, count should be 1
    $limiter->canSendNotification($user, 'mail');
    expect($limiter->getCurrentCount($user, 'mail'))->toBe(1);

    // After second request, count should be 2
    $limiter->canSendNotification($user, 'mail');
    expect($limiter->getCurrentCount($user, 'mail'))->toBe(2);
});

test('notification rate limiter resets rate limit correctly', function () {
    $user = User::factory()->create();
    $limiter = new NotificationRateLimiter;

    config([
        'notifications.rate_limits.mail.limit' => 2,
        'notifications.rate_limits.mail.window' => 60,
    ]);

    // Use up the limit
    $limiter->canSendNotification($user, 'mail');
    $limiter->canSendNotification($user, 'mail');

    // Should be blocked
    expect($limiter->canSendNotification($user, 'mail'))->toBeFalse();

    // Reset the limit
    $limiter->resetRateLimit($user, 'mail');

    // Should now be allowed
    expect($limiter->canSendNotification($user, 'mail'))->toBeTrue();
});

test('notification rate limiter provides user stats correctly', function () {
    $user = User::factory()->create();
    $limiter = new NotificationRateLimiter;

    config([
        'notifications.rate_limits.mail.limit' => 10,
        'notifications.rate_limits.mail.window' => 60,
        'notifications.rate_limits.slack.limit' => 5,
        'notifications.rate_limits.slack.window' => 30,
    ]);

    // Use some limits
    $limiter->canSendNotification($user, 'mail');
    $limiter->canSendNotification($user, 'mail');
    $limiter->canSendNotification($user, 'slack');

    $stats = $limiter->getUserRateLimitStats($user);

    expect($stats)->toHaveKey('mail');
    expect($stats)->toHaveKey('slack');
    expect($stats)->toHaveKey('discord');
    expect($stats)->toHaveKey('push');

    expect($stats['mail']['current'])->toBe(2);
    expect($stats['mail']['limit'])->toBe(10);
    expect($stats['mail']['remaining'])->toBe(8);

    expect($stats['slack']['current'])->toBe(1);
    expect($stats['slack']['limit'])->toBe(5);
    expect($stats['slack']['remaining'])->toBe(4);
});

test('notification rate limiter identifies rate limited users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $limiter = new NotificationRateLimiter;

    config([
        'notifications.rate_limits.mail.limit' => 1,
        'notifications.rate_limits.mail.window' => 60,
    ]);

    // Create notification settings for users
    $user1->notificationSetting()->create([
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    $user2->notificationSetting()->create([
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    // Use up limit for user1
    $limiter->canSendNotification($user1, 'mail');

    // User2 should still be under limit
    $limiter->canSendNotification($user2, 'mail');

    $rateLimitedUsers = $limiter->getRateLimitedUsers();

    expect($rateLimitedUsers)->toHaveCount(1);
    expect($rateLimitedUsers->first()->id)->toBe($user1->id);
});

test('notification rate limiter handles different channels independently', function () {
    $user = User::factory()->create();
    $limiter = new NotificationRateLimiter;

    config([
        'notifications.rate_limits.mail.limit' => 1,
        'notifications.rate_limits.mail.window' => 60,
        'notifications.rate_limits.slack.limit' => 1,
        'notifications.rate_limits.slack.window' => 60,
    ]);

    // Use up mail limit
    $limiter->canSendNotification($user, 'mail');
    expect($limiter->canSendNotification($user, 'mail'))->toBeFalse();

    // Slack should still be available
    expect($limiter->canSendNotification($user, 'slack'))->toBeTrue();
});

test('notification rate limiter uses default config when not specified', function () {
    $user = User::factory()->create();
    $limiter = new NotificationRateLimiter;

    // Clear config for testing defaults
    config()->offsetUnset('notifications.rate_limits');

    $result = $limiter->canSendNotification($user, 'mail');

    expect($result)->toBeTrue();
    expect($limiter->getCurrentCount($user, 'mail'))->toBe(1);
});

test('notification rate limiter cleanup expired entries returns zero', function () {
    $limiter = new NotificationRateLimiter;

    $result = $limiter->cleanupExpiredEntries();

    expect($result)->toBe(0);
});

test('notification rate limiter works with database rate limits', function () {
    $user = User::factory()->create();
    $limiter = new NotificationRateLimiter;

    // Create a rate limit record that's blocked
    NotificationRateLimit::factory()->create([
        'user_id' => $user->id,
        'channel' => 'mail',
        'action' => 'send_notification',
        'attempts' => 5,
        'is_blocked' => true,
        'reset_at' => now()->addHour(),
    ]);

    // Create rate limit config
    NotificationRateLimitConfig::factory()->create([
        'channel' => 'mail',
        'action' => 'send_notification',
        'max_attempts' => 3,
        'block_duration_minutes' => 60,
        'is_active' => true,
    ]);

    // The limiter should still work with cache-based limits
    config([
        'notifications.rate_limits.mail.limit' => 5,
        'notifications.rate_limits.mail.window' => 60,
    ]);

    expect($limiter->canSendNotification($user, 'mail'))->toBeTrue();
});

test('notification rate limiter handles multiple users independently', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $limiter = new NotificationRateLimiter;

    config([
        'notifications.rate_limits.mail.limit' => 1,
        'notifications.rate_limits.mail.window' => 60,
    ]);

    // User1 uses limit
    $limiter->canSendNotification($user1, 'mail');
    expect($limiter->canSendNotification($user1, 'mail'))->toBeFalse();

    // User2 should still be able to send
    expect($limiter->canSendNotification($user2, 'mail'))->toBeTrue();
    expect($limiter->canSendNotification($user2, 'mail'))->toBeFalse();
});

test('notification rate limiter generates correct cache keys', function () {
    $user = User::factory()->create(['id' => 123]);
    $limiter = new NotificationRateLimiter;

    // Use reflection to access protected method
    $reflection = new \ReflectionClass($limiter);
    $method = $reflection->getMethod('getRateLimitKey');
    $method->setAccessible(true);

    $key = $method->invoke($limiter, $user->id, 'mail');

    expect($key)->toBe('notification_rate_limit:mail:123');
});
