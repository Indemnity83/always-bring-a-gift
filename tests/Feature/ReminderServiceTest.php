<?php

use App\Models\Event;
use App\Models\EventNotificationLog;
use App\Models\EventType;
use App\Models\NotificationSetting;
use App\Models\Person;
use App\Models\User;
use App\Notifications\UpcomingEventReminderNotification;
use App\Services\ReminderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

if (! extension_loaded('pdo_sqlite')) {
    test('pdo_sqlite extension is required for reminder tests')->markTestSkipped('pdo_sqlite extension is missing in this environment.');

    return;
}

beforeEach(function () {
    Notification::fake();
    Http::fake();
    Carbon::setTestNow('2025-06-01 09:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

test('sends mail reminders for upcoming events and logs them', function () {
    config([
        'reminders.channels.mail.enabled' => true,
        'mail.default' => 'log',
    ]);

    $user = User::factory()->create();
    NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail'],
        'lead_time_days' => 10,
    ]);

    $person = Person::factory()->create([
        'user_id' => $user->id,
        'name' => 'Alex',
    ]);
    $eventType = EventType::factory()->create(['name' => 'Birthday']);

    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'is_annual' => true,
        'date' => '1990-06-05',
    ]);

    app(ReminderService::class)->sendUpcomingReminders();

    Notification::assertSentTo(
        $user,
        UpcomingEventReminderNotification::class,
        fn ($notification) => $notification->event()->is($event)
            && $notification->channelName() === 'mail'
    );

    expect(EventNotificationLog::count())->toBe(1);
});

test('skips reminders that were already sent for the same day and channel', function () {
    $user = User::factory()->create();
    NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail'],
    ]);

    $person = Person::factory()->create([
        'user_id' => $user->id,
        'name' => 'Jordan',
    ]);
    $eventType = EventType::factory()->create(['name' => 'Anniversary']);

    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'is_annual' => true,
        'date' => '2020-06-02',
    ]);

    EventNotificationLog::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'channel' => 'mail',
        'remind_for_date' => $event->next_occurrence->toDateString(),
        'sent_at' => now()->subDay(),
    ]);

    app(ReminderService::class)->sendUpcomingReminders();

    Notification::assertNothingSent();
    expect(EventNotificationLog::count())->toBe(1);
});

test('sends to configured webhooks when channels are enabled', function () {
    $user = User::factory()->create();
    NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['slack', 'discord', 'push'],
        'slack_webhook_url' => 'https://hooks.slack.test/123',
        'discord_webhook_url' => 'https://discord.test/webhook/456',
        'push_endpoint' => 'https://push.example.com/notify',
        'push_token' => 'push-token',
        'lead_time_days' => 5,
    ]);

    $person = Person::factory()->create([
        'user_id' => $user->id,
        'name' => 'Sam',
    ]);
    $eventType = EventType::factory()->create(['name' => 'Celebration']);

    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'is_annual' => true,
        'date' => '2010-06-03',
    ]);

    app(ReminderService::class)->sendUpcomingReminders();

    Notification::assertSentOnDemand(
        UpcomingEventReminderNotification::class,
        fn ($notification, $channels, $notifiable) => ($notifiable->routes['slack'] ?? null) === 'https://hooks.slack.test/123'
            && $notification->event()->is($event)
    );

    Notification::assertSentOnDemand(
        UpcomingEventReminderNotification::class,
        fn ($notification, $channels, $notifiable) => ($notifiable->routes['discord'] ?? null) === 'https://discord.test/webhook/456'
            && $notification->event()->is($event)
    );

    Notification::assertSentOnDemand(
        UpcomingEventReminderNotification::class,
        fn ($notification, $channels, $notifiable) => ($notifiable->routes['push']['endpoint'] ?? null) === 'https://push.example.com/notify'
            && $notification->event()->is($event)
    );

    expect(
        EventNotificationLog::where('user_id', $user->id)->pluck('channel')->sort()->values()->all()
    )->toBe(['discord', 'push', 'slack']);
});

test('respects user timezone when determining if reminder should send', function () {
    // Server time is 09:00 UTC (set in beforeEach)
    // User is in America/Los_Angeles (UTC-7 in June), so local time is 02:00
    // Reminder is set for 09:00, so it should NOT send yet

    config([
        'reminders.channels.mail.enabled' => true,
        'mail.default' => 'log',
    ]);

    $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);
    NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail'],
        'lead_time_days' => 10,
        'remind_at' => '09:00:00',
    ]);

    $person = Person::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Person',
    ]);
    $eventType = EventType::factory()->create(['name' => 'Birthday']);

    Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'is_annual' => true,
        'date' => '1990-06-05',
    ]);

    app(ReminderService::class)->sendUpcomingReminders();

    // Should NOT send because it's 02:00 in user's timezone
    Notification::assertNothingSent();
});

test('sends reminder when user local time has passed remind_at time', function () {
    // Server time is 09:00 UTC (set in beforeEach)
    // User is in Europe/London (UTC+1 in June due to BST), so local time is 10:00
    // Reminder is set for 09:00, so it SHOULD send

    config([
        'reminders.channels.mail.enabled' => true,
        'mail.default' => 'log',
    ]);

    $user = User::factory()->create(['timezone' => 'Europe/London']);
    NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail'],
        'lead_time_days' => 10,
        'remind_at' => '09:00:00',
    ]);

    $person = Person::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Person',
    ]);
    $eventType = EventType::factory()->create(['name' => 'Birthday']);

    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'is_annual' => true,
        'date' => '1990-06-05',
    ]);

    app(ReminderService::class)->sendUpcomingReminders();

    // Should send because it's 10:00 in user's timezone
    Notification::assertSentTo(
        $user,
        UpcomingEventReminderNotification::class,
        fn ($notification) => $notification->event()->is($event)
    );
});
