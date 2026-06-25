<?php

use App\Models\Event;
use App\Models\EventNotificationLog;
use App\Models\EventType;
use App\Models\NotificationAnalytics;
use App\Models\Person;
use App\Models\User;
use App\Services\NotificationAnalyticsService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Sequence;

function createNotificationLogs(int $count, array $attributes = []): void
{
    $sequence = [];
    for ($index = 0; $index < $count; $index++) {
        $sequence[] = [
            'remind_for_date' => now()->subDays($index + 1)->toDateString(),
        ];
    }

    EventNotificationLog::factory()
        ->count($count)
        ->state(new Sequence(...$sequence))
        ->create($attributes);
}

beforeEach(function () {
    // Set test time for consistent analytics
    Carbon::setTestNow('2025-06-01 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

test('notification analytics service provides user analytics correctly', function () {
    $user = User::factory()->create();
    $service = new NotificationAnalyticsService;

    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create notification logs for the user
    createNotificationLogs(5, [
        'user_id' => $user->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(rand(1, 30)),
    ]);

    $analytics = $service->getUserAnalytics($user);

    expect($analytics)->toBeArray();
    expect($analytics)->toHaveKeys(['user_id', 'period', 'total_notifications', 'by_channel', 'by_day', 'success_rate', 'average_per_day', 'most_active_day', 'channel_preferences']);
    expect($analytics['user_id'])->toBe($user->id);
    expect($analytics['total_notifications'])->toBe(5);
    expect($analytics['by_channel'])->toHaveKey('mail');
    expect($analytics['by_channel']['mail'])->toBe(5);
    expect($analytics['success_rate'])->toBe(100.0);
});

test('notification analytics service provides system analytics correctly', function () {
    $service = new NotificationAnalyticsService;

    // Create multiple users with notification logs
    $users = User::factory()->count(3)->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    foreach ($users as $user) {
        // Create notification settings for each user
        $user->notificationSetting()->create([
            'channels' => ['mail'],
            'lead_time_days' => 7,
        ]);

        // Create notification logs for each user
        createNotificationLogs(2, [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'channel' => 'mail',
            'sent_at' => now()->subDays(rand(1, 30)),
        ]);
    }

    $analytics = $service->getSystemAnalytics();

    expect($analytics)->toBeArray();
    expect($analytics)->toHaveKeys(['period', 'total_notifications', 'total_users', 'active_users', 'by_channel', 'by_day', 'success_rate', 'average_per_user', 'average_per_day', 'top_users', 'channel_distribution', 'growth_trend']);
    expect($analytics['total_users'])->toBe(3);
    expect($analytics['active_users'])->toBe(3);
    expect($analytics['total_notifications'])->toBe(6);
    expect($analytics['average_per_user'])->toBe(2.0);
});

test('notification analytics service handles custom date ranges', function () {
    $user = User::factory()->create();
    $service = new NotificationAnalyticsService;

    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create notification logs within specific date range
    createNotificationLogs(3, [
        'user_id' => $user->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(10),
    ]);

    $startDate = now()->subDays(15);
    $endDate = now()->subDays(5);

    $analytics = $service->getUserAnalytics($user, $startDate, $endDate);

    expect($analytics['total_notifications'])->toBe(3);
    expect($analytics['period']['start'])->toBe($startDate->toISOString());
    expect($analytics['period']['end'])->toBe($endDate->toISOString());
});

test('notification analytics service handles empty logs', function () {
    $user = User::factory()->create();
    $service = new NotificationAnalyticsService;

    $analytics = $service->getUserAnalytics($user);

    expect($analytics['total_notifications'])->toBe(0);
    expect($analytics['by_channel'])->toBeEmpty();
    expect($analytics['by_day'])->toBeEmpty();
    expect($analytics['success_rate'])->toBe(0.0);
    expect($analytics['average_per_day'])->toBe(0.0);
    expect($analytics['most_active_day'])->toBeNull();
});

test('notification analytics service provides channel breakdown correctly', function () {
    $user = User::factory()->create();
    $service = new NotificationAnalyticsService;

    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create notification logs for different channels
    createNotificationLogs(3, [
        'user_id' => $user->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(rand(1, 30)),
    ]);

    createNotificationLogs(2, [
        'user_id' => $user->id,
        'event_id' => $event->id,
        'channel' => 'slack',
        'sent_at' => now()->subDays(rand(1, 30)),
    ]);

    createNotificationLogs(1, [
        'user_id' => $user->id,
        'event_id' => $event->id,
        'channel' => 'discord',
        'sent_at' => now()->subDays(rand(1, 30)),
    ]);

    $analytics = $service->getUserAnalytics($user);

    expect($analytics['by_channel'])->toBe([
        'mail' => 3,
        'slack' => 2,
        'discord' => 1,
    ]);
});

test('notification analytics service provides daily breakdown correctly', function () {
    $user = User::factory()->create();
    $service = new NotificationAnalyticsService;

    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create notification logs for different days
    EventNotificationLog::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(5),
        'remind_for_date' => now()->subDays(5)->toDateString(),
    ]);

    EventNotificationLog::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'channel' => 'slack',
        'sent_at' => now()->subDays(5),
        'remind_for_date' => now()->subDays(5)->toDateString(),
    ]);

    EventNotificationLog::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(3),
        'remind_for_date' => now()->subDays(3)->toDateString(),
    ]);

    $analytics = $service->getUserAnalytics($user);

    expect($analytics['by_day'])->toHaveKey(now()->subDays(5)->format('Y-m-d'));
    expect($analytics['by_day'][now()->subDays(5)->format('Y-m-d')])->toBe(2);
    expect($analytics['by_day'])->toHaveKey(now()->subDays(3)->format('Y-m-d'));
    expect($analytics['by_day'][now()->subDays(3)->format('Y-m-d')])->toBe(1);
});

test('notification analytics service calculates success rate correctly', function () {
    $user = User::factory()->create();
    $service = new NotificationAnalyticsService;

    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create notification logs (all successful by default)
    createNotificationLogs(5, [
        'user_id' => $user->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(rand(1, 30)),
    ]);

    $analytics = $service->getUserAnalytics($user);

    expect($analytics['success_rate'])->toBe(100.0);
});

test('notification analytics service calculates average per day correctly', function () {
    $user = User::factory()->create();
    $service = new NotificationAnalyticsService;

    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create 10 notification logs over 30 days
    createNotificationLogs(10, [
        'user_id' => $user->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(rand(1, 30)),
    ]);

    $analytics = $service->getUserAnalytics($user);

    expect($analytics['average_per_day'])->toBe(10.0 / 30.0);
});

test('notification analytics service identifies most active day correctly', function () {
    $user = User::factory()->create();
    $service = new NotificationAnalyticsService;

    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create notification logs with different frequencies
    createNotificationLogs(3, [
        'user_id' => $user->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(5),
    ]);

    EventNotificationLog::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(3),
        'remind_for_date' => now()->subDays(10)->toDateString(),
    ]);

    $analytics = $service->getUserAnalytics($user);

    expect($analytics['most_active_day'])->toBe(now()->subDays(5)->format('Y-m-d'));
});

test('notification analytics service provides channel preferences correctly', function () {
    $user = User::factory()->create();
    $service = new NotificationAnalyticsService;

    // Create notification settings with multiple channels
    $user->notificationSetting()->create([
        'channels' => ['mail', 'slack', 'discord'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'discord_webhook_url' => 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890',
        'lead_time_days' => 7,
    ]);

    $analytics = $service->getUserAnalytics($user);

    expect($analytics['channel_preferences'])->toBe([
        'enabled_channels' => ['mail', 'slack', 'discord'],
        'has_mail' => true,
        'has_slack' => true,
        'has_discord' => true,
        'has_push' => false,
    ]);
});

test('notification analytics service provides top users correctly', function () {
    $service = new NotificationAnalyticsService;

    $users = User::factory()->count(3)->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create different numbers of notification logs for each user
    createNotificationLogs(5, [
        'user_id' => $users[0]->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(rand(1, 30)),
    ]);

    createNotificationLogs(3, [
        'user_id' => $users[1]->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(rand(1, 30)),
    ]);

    createNotificationLogs(1, [
        'user_id' => $users[2]->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(rand(1, 30)),
    ]);

    $analytics = $service->getSystemAnalytics();

    expect($analytics['top_users'])->toBe([
        $users[0]->id => 5,
        $users[1]->id => 3,
        $users[2]->id => 1,
    ]);
});

test('notification analytics service provides system channel distribution correctly', function () {
    $service = new NotificationAnalyticsService;

    $users = User::factory()->count(3)->create();

    // Create different channel configurations for each user
    $users[0]->notificationSetting()->create([
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    $users[1]->notificationSetting()->create([
        'channels' => ['mail', 'slack'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'lead_time_days' => 7,
    ]);

    $users[2]->notificationSetting()->create([
        'channels' => ['mail', 'slack', 'discord'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'discord_webhook_url' => 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890',
        'lead_time_days' => 7,
    ]);

    $analytics = $service->getSystemAnalytics();

    expect($analytics['channel_distribution'])->toBe([
        'mail' => 3,
        'slack' => 2,
        'discord' => 1,
        'push' => 0,
    ]);
});

test('notification analytics service provides growth trend correctly', function () {
    $service = new NotificationAnalyticsService;

    $users = User::factory()->count(2)->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create notification logs for different days
    createNotificationLogs(2, [
        'user_id' => $users[0]->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(5),
    ]);

    createNotificationLogs(1, [
        'user_id' => $users[1]->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(3),
    ]);

    $analytics = $service->getSystemAnalytics();

    expect($analytics['growth_trend'])->toHaveKey(now()->subDays(5)->format('Y-m-d'));
    expect($analytics['growth_trend'][now()->subDays(5)->format('Y-m-d')])->toBe(2);
    expect($analytics['growth_trend'])->toHaveKey(now()->subDays(3)->format('Y-m-d'));
    expect($analytics['growth_trend'][now()->subDays(3)->format('Y-m-d')])->toBe(1);
});

test('notification analytics service provides time-based stats correctly', function () {
    $service = new NotificationAnalyticsService;

    $users = User::factory()->count(2)->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create notification logs for today
    createNotificationLogs(2, [
        'user_id' => $users[0]->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now(),
    ]);

    $stats = $service->getTimeBasedStats('today');

    expect($stats['total_notifications'])->toBe(2);
});

test('notification analytics service provides comparative analytics correctly', function () {
    $service = new NotificationAnalyticsService;

    $users = User::factory()->count(2)->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create notification logs for period 1
    createNotificationLogs(4, [
        'user_id' => $users[0]->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(10),
    ]);

    // Create notification logs for period 2
    createNotificationLogs(2, [
        'user_id' => $users[1]->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now()->subDays(40),
    ]);

    $period1Start = now()->subDays(15);
    $period1End = now()->subDays(5);
    $period2Start = now()->subDays(45);
    $period2End = now()->subDays(35);

    $comparative = $service->getComparativeAnalytics($period1Start, $period1End, $period2Start, $period2End);

    expect($comparative)->toHaveKeys(['period1', 'period2', 'changes']);
    expect($comparative['period1']['total_notifications'])->toBe(4);
    expect($comparative['period2']['total_notifications'])->toBe(2);
    expect($comparative['changes']['total_notifications'])->toBe(100.0); // 100% increase
});

test('notification analytics service handles users without notification settings', function () {
    $service = new NotificationAnalyticsService;

    $users = User::factory()->count(2)->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create notification settings for only one user
    $users[0]->notificationSetting()->create([
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    $analytics = $service->getSystemAnalytics();

    expect($analytics['active_users'])->toBe(1);
    expect($analytics['channel_distribution'])->toBe([
        'mail' => 1,
        'slack' => 0,
        'discord' => 0,
        'push' => 0,
    ]);
});

test('notification analytics service handles notification analytics model correctly', function () {
    $service = new NotificationAnalyticsService;

    // Create notification analytics records
    NotificationAnalytics::factory()->count(3)->create([
        'channel' => 'mail',
        'notification_type' => 'event_reminder',
        'date' => now()->subDays(rand(1, 30)),
    ]);

    // The service should work with existing analytics data
    $analytics = $service->getSystemAnalytics();

    expect($analytics)->toBeArray();
    expect($analytics)->toHaveKey('total_notifications');
});
