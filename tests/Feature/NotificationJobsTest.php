<?php

use App\Jobs\BatchSendNotificationsJob;
use App\Jobs\SendNotificationJob;
use App\Models\Event;
use App\Models\EventNotificationLog;
use App\Models\EventType;
use App\Models\NotificationRateLimit;
use App\Models\NotificationRateLimitConfig;
use App\Models\Person;
use App\Models\User;
use App\Notifications\UpcomingEventReminderNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Notification::fake();
    Log::shouldReceive('channel')->andReturnSelf();
});

afterEach(function () {
    Queue::fake();
    Notification::fake();
});

test('send notification job dispatches correctly', function () {
    $user = User::factory()->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    $notification = new UpcomingEventReminderNotification($event, $event->next_occurrence, $user, 'mail', 7);
    $job = new SendNotificationJob($user, $notification, 'mail', $user, $event->id, $event->next_occurrence);

    $job->handle();

    // Notification should be sent to the user
    Notification::assertSentTo($user, UpcomingEventReminderNotification::class);
});

test('send notification job handles rate limiting correctly', function () {
    $user = User::factory()->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create a blocked rate limit
    NotificationRateLimit::factory()->create([
        'user_id' => $user->id,
        'channel' => 'mail',
        'action' => 'send_notification',
        'is_blocked' => true,
        'reset_at' => now()->addHour(),
    ]);

    $notification = new UpcomingEventReminderNotification($event, $event->next_occurrence, $user, 'mail', 7);
    $job = new SendNotificationJob($user, $notification, 'mail', $user, $event->id, $event->next_occurrence);

    $job->handle();

    // Job should be released due to rate limiting
    Queue::assertPushed(SendNotificationJob::class, function ($job) {
        return $job->attempts() > 0;
    });
});

test('send notification job handles expired rate limits correctly', function () {
    $user = User::factory()->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create an expired rate limit
    NotificationRateLimit::factory()->create([
        'user_id' => $user->id,
        'channel' => 'mail',
        'action' => 'send_notification',
        'is_blocked' => true,
        'reset_at' => now()->subHour(),
    ]);

    $notification = new UpcomingEventReminderNotification($event, $event->next_occurrence, $user, 'mail', 7);
    $job = new SendNotificationJob($user, $notification, 'mail', $user, $event->id, $event->next_occurrence);

    $job->handle();

    // Rate limit should be reset and notification sent
    Notification::assertSentTo($user, UpcomingEventReminderNotification::class);
});

test('send notification job handles different channels correctly', function () {
    $user = User::factory()->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    $channels = ['mail', 'slack', 'discord', 'push'];

    foreach ($channels as $channel) {
        $notification = new UpcomingEventReminderNotification($event, $event->next_occurrence, $user, $channel, 7);
        $job = new SendNotificationJob($user, $notification, $channel, $user, $event->id, $event->next_occurrence);

        $job->handle();

        if ($channel === 'mail') {
            Notification::assertSentTo($user, UpcomingEventReminderNotification::class);
        } else {
            Notification::assertSentOnDemand(UpcomingEventReminderNotification::class);
        }
    }
});

test('send notification job handles unsupported channels correctly', function () {
    $user = User::factory()->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    $notification = new UpcomingEventReminderNotification($event, $event->next_occurrence, $user, 'unsupported', 7);
    $job = new SendNotificationJob($user, $notification, 'unsupported', $user, $event->id, $event->next_occurrence);

    expect(function () use ($job) {
        $job->handle();
    })->toThrow(\InvalidArgumentException::class);
});

test('send notification job logs delivery correctly', function () {
    $user = User::factory()->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    $notification = new UpcomingEventReminderNotification($event, $event->next_occurrence, $user, 'mail', 7);
    $job = new SendNotificationJob($user, $notification, 'mail', $user, $event->id, $event->next_occurrence);

    Log::shouldReceive('channel->log')->once();

    $job->handle();
});

test('send notification job updates rate limit on success', function () {
    $user = User::factory()->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    $notification = new UpcomingEventReminderNotification($event, $event->next_occurrence, $user, 'mail', 7);
    $job = new SendNotificationJob($user, $notification, 'mail', $user, $event->id, $event->next_occurrence);

    $job->handle();

    // Rate limit should be created or updated
    expect(NotificationRateLimit::where('user_id', $user->id)
        ->where('channel', 'mail')
        ->where('action', 'send_notification')
        ->exists())->toBeTrue();
});

test('send notification job updates rate limit on failure', function () {
    $user = User::factory()->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    $notification = new UpcomingEventReminderNotification($event, $event->next_occurrence, $user, 'mail', 7);
    $job = new SendNotificationJob($user, $notification, 'mail', $user, $event->id, $event->next_occurrence, true);
    $job->tries = 1;

    // Create rate limit config
    NotificationRateLimitConfig::factory()->create([
        'channel' => 'mail',
        'action' => 'send_notification',
        'max_attempts' => 3,
        'block_duration_minutes' => 60,
        'is_active' => true,
    ]);

    expect(fn () => $job->handle())->toThrow(\Exception::class);

    // Rate limit should be updated with increased attempts
    $rateLimit = NotificationRateLimit::where('user_id', $user->id)
        ->where('channel', 'mail')
        ->where('action', 'send_notification')
        ->first();

    expect($rateLimit->attempts)->toBe(1);
});

test('send notification job handles job failure correctly', function () {
    $user = User::factory()->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    $notification = new UpcomingEventReminderNotification($event, $event->next_occurrence, $user, 'mail', 7);
    $job = new SendNotificationJob($user, $notification, 'mail', $user, $event->id, $event->next_occurrence);

    Log::shouldReceive('error')->once();

    $job->failed(new \Exception('Test failure'));

    // Notification log should be created with null sent_at
    expect(EventNotificationLog::where('user_id', $user->id)
        ->where('event_id', $event->id)
        ->where('channel', 'mail')
        ->whereNull('sent_at')
        ->exists())->toBeTrue();
});

test('send notification job has correct tags', function () {
    $user = User::factory()->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    $notification = new UpcomingEventReminderNotification($event, $event->next_occurrence, $user, 'mail', 7);
    $job = new SendNotificationJob($user, $notification, 'mail', $user, $event->id, $event->next_occurrence);

    $tags = $job->tags();

    expect($tags)->toBeArray();
    expect($tags)->toContain('notification');
    expect($tags)->toContain('user:'.$user->id);
    expect($tags)->toContain('channel:mail');
    expect($tags)->toContain('event:'.$event->id);
});

test('batch send notifications job processes notifications correctly', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $eventType = EventType::factory()->create();
    $person1 = Person::factory()->create();
    $person2 = Person::factory()->create();
    $event1 = Event::factory()->create([
        'person_id' => $person1->id,
        'event_type_id' => $eventType->id,
    ]);
    $event2 = Event::factory()->create([
        'person_id' => $person2->id,
        'event_type_id' => $eventType->id,
    ]);

    $notifications = collect([
        [
            'user_id' => $user1->id,
            'event_id' => $event1->id,
            'channel' => 'mail',
            'target' => $user1,
            'event' => $event1,
            'occurrence_date' => $event1->next_occurrence,
            'days_away' => 7,
        ],
        [
            'user_id' => $user2->id,
            'event_id' => $event2->id,
            'channel' => 'mail',
            'target' => $user2,
            'event' => $event2,
            'occurrence_date' => $event2->next_occurrence,
            'days_away' => 7,
        ],
    ]);

    $job = new BatchSendNotificationsJob($notifications);

    $job->handle();

    // Should dispatch individual notification jobs
    Queue::assertPushed(SendNotificationJob::class, 2);
});

test('batch send notifications job handles missing users correctly', function () {
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    $notifications = collect([
        [
            'user_id' => 999, // Non-existent user
            'event_id' => $event->id,
            'channel' => 'mail',
            'target' => null,
            'event' => $event,
            'occurrence_date' => $event->next_occurrence,
            'days_away' => 7,
        ],
    ]);

    $job = new BatchSendNotificationsJob($notifications);

    Log::shouldReceive('warning')->once();

    $job->handle();

    // Should not dispatch any jobs for missing users
    Queue::assertNotPushed(SendNotificationJob::class);
});

test('batch send notifications job skips already sent notifications', function () {
    $user = User::factory()->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create existing notification log
    EventNotificationLog::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'channel' => 'mail',
        'sent_at' => now(),
        'remind_for_date' => $event->next_occurrence->toDateString(),
    ]);

    $notifications = collect([
        [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'channel' => 'mail',
            'target' => $user,
            'event' => $event,
            'occurrence_date' => $event->next_occurrence,
            'days_away' => 7,
        ],
    ]);

    $job = new BatchSendNotificationsJob($notifications);

    $job->handle();

    // Should not dispatch job for already sent notification
    Queue::assertNotPushed(SendNotificationJob::class);
});

test('batch send notifications job handles rate limiting correctly', function () {
    $user = User::factory()->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    // Create a blocked rate limit
    NotificationRateLimit::factory()->create([
        'user_id' => $user->id,
        'channel' => 'mail',
        'action' => 'send_notification',
        'is_blocked' => true,
        'reset_at' => now()->addHour(),
    ]);

    $notifications = collect([
        [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'channel' => 'mail',
            'target' => $user,
            'event' => $event,
            'occurrence_date' => $event->next_occurrence,
            'days_away' => 7,
        ],
    ]);

    $job = new BatchSendNotificationsJob($notifications);

    $job->handle();

    // Should not dispatch job for rate limited user
    Queue::assertNotPushed(SendNotificationJob::class);
});

test('batch send notifications job logs processing correctly', function () {
    $user = User::factory()->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    $notifications = collect([
        [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'channel' => 'mail',
            'target' => $user,
            'event' => $event,
            'occurrence_date' => $event->next_occurrence,
            'days_away' => 7,
        ],
    ]);

    $job = new BatchSendNotificationsJob($notifications);

    Log::shouldReceive('info')->once();

    $job->handle();
});

test('batch send notifications job handles job failure correctly', function () {
    $notifications = collect([
        [
            'user_id' => 1,
            'event_id' => 1,
            'channel' => 'mail',
            'target' => null,
            'event' => null,
            'occurrence_date' => now(),
            'days_away' => 7,
        ],
    ]);

    $job = new BatchSendNotificationsJob($notifications);

    Log::shouldReceive('error')->once();

    $job->failed(new \Exception('Test failure'));
});

test('batch send notifications job has correct tags', function () {
    $notifications = collect([
        [
            'user_id' => 1,
            'event_id' => 1,
            'channel' => 'mail',
            'target' => null,
            'event' => null,
            'occurrence_date' => now(),
            'days_away' => 7,
        ],
    ]);

    $job = new BatchSendNotificationsJob($notifications);

    $tags = $job->tags();

    expect($tags)->toBeArray();
    expect($tags)->toContain('batch_notification');
    expect($tags)->toContain('count:1');
});

test('batch send notifications job processes in chunks correctly', function () {
    $notifications = collect();

    // Create 100 notifications
    for ($i = 0; $i < 100; $i++) {
        $user = User::factory()->create();
        $eventType = EventType::factory()->create();
        $person = Person::factory()->create();
        $event = Event::factory()->create([
            'person_id' => $person->id,
            'event_type_id' => $eventType->id,
        ]);

        $notifications->push([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'channel' => 'mail',
            'target' => $user,
            'event' => $event,
            'occurrence_date' => $event->next_occurrence,
            'days_away' => 7,
        ]);
    }

    $job = new BatchSendNotificationsJob($notifications, 10); // Batch size of 10

    $job->handle();

    // Should dispatch 100 individual notification jobs
    Queue::assertPushed(SendNotificationJob::class, 100);
});
