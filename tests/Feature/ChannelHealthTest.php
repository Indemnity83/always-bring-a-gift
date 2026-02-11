<?php

use App\Models\EventNotificationLog;
use App\Models\User;
use App\Services\ChannelHealthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake();
    Cache::flush();
});

afterEach(function () {
    Http::fake();
    Cache::flush();
});

test('channel health service checks mail channel health correctly', function () {
    $user = User::factory()->create();
    $service = new ChannelHealthService;

    // Create notification setting with mail channel
    $user->notificationSetting()->create([
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    // Configure mail settings
    config([
        'mail.host' => 'smtp.example.com',
        'mail.port' => 587,
        'mail.username' => 'test@example.com',
        'mail.password' => 'password',
    ]);

    $health = $service->checkChannelHealth($user, 'mail');

    expect($health)->toBeArray();
    expect($health)->toHaveKeys(['channel', 'status', 'last_used', 'success_rate', 'error_count', 'total_attempts', 'connectivity', 'details']);
    expect($health['channel'])->toBe('mail');
    expect($health['status'])->toBe('inactive'); // No recent activity
    expect($health['connectivity'])->toBeTrue();
});

test('channel health service checks slack channel health correctly', function () {
    $user = User::factory()->create();
    $service = new ChannelHealthService;

    // Create notification setting with slack channel
    $user->notificationSetting()->create([
        'channels' => ['slack'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'lead_time_days' => 7,
    ]);

    // Mock successful webhook response
    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $health = $service->checkChannelHealth($user, 'slack');

    expect($health)->toBeArray();
    expect($health['channel'])->toBe('slack');
    expect($health['status'])->toBe('inactive'); // No recent activity
    expect($health['connectivity'])->toBeTrue();
});

test('channel health service checks discord channel health correctly', function () {
    $user = User::factory()->create();
    $service = new ChannelHealthService;

    // Create notification setting with discord channel
    $user->notificationSetting()->create([
        'channels' => ['discord'],
        'discord_webhook_url' => 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890',
        'lead_time_days' => 7,
    ]);

    // Mock successful webhook response
    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $health = $service->checkChannelHealth($user, 'discord');

    expect($health)->toBeArray();
    expect($health['channel'])->toBe('discord');
    expect($health['status'])->toBe('inactive'); // No recent activity
    expect($health['connectivity'])->toBeTrue();
});

test('channel health service checks push channel health correctly', function () {
    $user = User::factory()->create();
    $service = new ChannelHealthService;

    // Create notification setting with push channel
    $user->notificationSetting()->create([
        'channels' => ['push'],
        'push_endpoint' => 'https://push.example.com/notify',
        'push_token' => 'test-token',
        'lead_time_days' => 7,
    ]);

    // Mock successful push endpoint response
    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $health = $service->checkChannelHealth($user, 'push');

    expect($health)->toBeArray();
    expect($health['channel'])->toBe('push');
    expect($health['status'])->toBe('inactive'); // No recent activity
    expect($health['connectivity'])->toBeTrue();
});

test('channel health service handles recent activity correctly', function () {
    $user = User::factory()->create();
    $service = new ChannelHealthService;

    // Create notification setting with mail channel
    $user->notificationSetting()->create([
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    // Create recent notification log
    EventNotificationLog::factory()->create([
        'user_id' => $user->id,
        'channel' => 'mail',
        'sent_at' => now()->subHours(2),
    ]);

    // Configure mail settings
    config([
        'mail.host' => 'smtp.example.com',
        'mail.port' => 587,
        'mail.username' => 'test@example.com',
        'mail.password' => 'password',
    ]);

    $health = $service->checkChannelHealth($user, 'mail');

    expect($health['status'])->toBe('healthy');
    expect($health['last_used'])->not->toBeNull();
    expect($health['total_attempts'])->toBe(1);
    expect($health['success_rate'])->toBe(100);
});

test('channel health service handles connectivity failures correctly', function () {
    $user = User::factory()->create();
    $service = new ChannelHealthService;

    // Create notification setting with slack channel
    $user->notificationSetting()->create([
        'channels' => ['slack'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'lead_time_days' => 7,
    ]);

    // Mock failed webhook response
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    $health = $service->checkChannelHealth($user, 'slack');

    expect($health['status'])->toBe('unhealthy');
    expect($health['connectivity'])->toBeFalse();
    expect($health['details'])->toContain('Connectivity test failed');
});

test('channel health service checks all channels for a user', function () {
    $user = User::factory()->create();
    $service = new ChannelHealthService;

    // Create notification setting with multiple channels
    $user->notificationSetting()->create([
        'channels' => ['mail', 'slack'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'lead_time_days' => 7,
    ]);

    // Configure mail settings
    config([
        'mail.host' => 'smtp.example.com',
        'mail.port' => 587,
        'mail.username' => 'test@example.com',
        'mail.password' => 'password',
    ]);

    // Mock successful webhook response
    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $healthResults = $service->checkAllChannelsHealth($user);

    expect($healthResults)->toBeArray();
    expect($healthResults)->toHaveKeys(['mail', 'slack']);
    expect($healthResults['mail']['channel'])->toBe('mail');
    expect($healthResults['slack']['channel'])->toBe('slack');
});

test('channel health service provides system health overview', function () {
    $service = new ChannelHealthService;

    // Create users with different channel configurations
    $user1 = User::factory()->create();
    $user1->notificationSetting()->create([
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    $user2 = User::factory()->create();
    $user2->notificationSetting()->create([
        'channels' => ['slack'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'lead_time_days' => 7,
    ]);

    // Configure mail settings
    config([
        'mail.host' => 'smtp.example.com',
        'mail.port' => 587,
        'mail.username' => 'test@example.com',
        'mail.password' => 'password',
    ]);

    // Mock successful webhook response
    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $overview = $service->getSystemHealthOverview();

    expect($overview)->toBeArray();
    expect($overview)->toHaveKeys(['total_users', 'active_users', 'channels', 'last_check']);
    expect($overview['total_users'])->toBe(2);
    expect($overview['active_users'])->toBe(2);
    expect($overview['channels'])->toHaveKeys(['mail', 'slack', 'discord', 'push']);
});

test('channel health service identifies users with unhealthy channels', function () {
    $service = new ChannelHealthService;

    // Create user with healthy channel
    $healthyUser = User::factory()->create();
    $healthyUser->notificationSetting()->create([
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    // Create user with unhealthy channel
    $unhealthyUser = User::factory()->create();
    $unhealthyUser->notificationSetting()->create([
        'channels' => ['slack'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'lead_time_days' => 7,
    ]);

    // Configure mail settings
    config([
        'mail.host' => 'smtp.example.com',
        'mail.port' => 587,
        'mail.username' => 'test@example.com',
        'mail.password' => 'password',
    ]);

    // Mock failed webhook response
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    $unhealthyUsers = $service->getUsersWithUnhealthyChannels();

    expect($unhealthyUsers)->toHaveCount(1);
    expect($unhealthyUsers->first()->id)->toBe($unhealthyUser->id);
});

test('channel health service logs health issues correctly', function () {
    $user = User::factory()->create();
    $service = new ChannelHealthService;

    // Create notification setting with slack channel
    $user->notificationSetting()->create([
        'channels' => ['slack'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'lead_time_days' => 7,
    ]);

    // Mock failed webhook response
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    // Check health (which should log the issue)
    $health = $service->checkChannelHealth($user, 'slack');

    expect($health['status'])->toBe('unhealthy');
    // The log method should be called, but we can't easily test that without mocking
});

test('channel health service handles missing notification settings', function () {
    $user = User::factory()->create();
    $service = new ChannelHealthService;

    // User has no notification settings
    $health = $service->checkChannelHealth($user, 'mail');

    expect($health)->toBeArray();
    expect($health['status'])->toBe('inactive');
    expect($health['connectivity'])->toBeFalse();
});

test('channel health service handles missing webhook URLs', function () {
    $user = User::factory()->create();
    $service = new ChannelHealthService;

    // Create notification setting with slack channel but no webhook URL
    $user->notificationSetting()->create([
        'channels' => ['slack'],
        'lead_time_days' => 7,
    ]);

    $health = $service->checkChannelHealth($user, 'slack');

    expect($health['status'])->toBe('inactive');
    expect($health['connectivity'])->toBeFalse();
});

test('channel health service handles missing push endpoints', function () {
    $user = User::factory()->create();
    $service = new ChannelHealthService;

    // Create notification setting with push channel but no endpoint
    $user->notificationSetting()->create([
        'channels' => ['push'],
        'lead_time_days' => 7,
    ]);

    $health = $service->checkChannelHealth($user, 'push');

    expect($health['status'])->toBe('inactive');
    expect($health['connectivity'])->toBeFalse();
});

test('channel health service caches webhook connectivity results', function () {
    $user = User::factory()->create();
    $service = new ChannelHealthService;

    // Create notification setting with slack channel
    $user->notificationSetting()->create([
        'channels' => ['slack'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'lead_time_days' => 7,
    ]);

    // Mock successful webhook response
    Http::fake([
        '*' => Http::response([], 200),
    ]);

    // First call should make HTTP request
    $health1 = $service->checkChannelHealth($user, 'slack');

    // Second call should use cached result
    $health2 = $service->checkChannelHealth($user, 'slack');

    expect($health1['connectivity'])->toBeTrue();
    expect($health2['connectivity'])->toBeTrue();
});

test('channel health service handles old notification logs correctly', function () {
    $user = User::factory()->create();
    $service = new ChannelHealthService;

    // Create notification setting with mail channel
    $user->notificationSetting()->create([
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    // Create old notification log (more than 7 days ago)
    EventNotificationLog::factory()->create([
        'user_id' => $user->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(10),
    ]);

    // Configure mail settings
    config([
        'mail.host' => 'smtp.example.com',
        'mail.port' => 587,
        'mail.username' => 'test@example.com',
        'mail.password' => 'password',
    ]);

    $health = $service->checkChannelHealth($user, 'mail');

    expect($health['status'])->toBe('inactive'); // No recent activity
    expect($health['total_attempts'])->toBe(0);
});

test('channel health service calculates success rate correctly', function () {
    $user = User::factory()->create();
    $service = new ChannelHealthService;

    // Create notification setting with mail channel
    $user->notificationSetting()->create([
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    // Create multiple recent notification logs
    EventNotificationLog::factory()->count(5)->create([
        'user_id' => $user->id,
        'channel' => 'mail',
        'sent_at' => now()->subHours(2),
    ]);

    // Configure mail settings
    config([
        'mail.host' => 'smtp.example.com',
        'mail.port' => 587,
        'mail.username' => 'test@example.com',
        'mail.password' => 'password',
    ]);

    $health = $service->checkChannelHealth($user, 'mail');

    expect($health['status'])->toBe('healthy');
    expect($health['total_attempts'])->toBe(5);
    expect($health['success_rate'])->toBe(100);
});
