<?php

use App\Models\Event;
use App\Models\EventType;
use App\Models\NotificationPreference;
use App\Models\NotificationSetting;
use App\Models\NotificationTemplate;
use App\Models\Person;
use App\Models\User;
use App\Services\WebhookValidationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Http::fake();
});

afterEach(function () {
    Http::fake();
});

test('notification settings can be created for a user', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail', 'slack'],
        'lead_time_days' => 7,
    ]);

    expect($settings->user_id)->toBe($user->id);
    expect($settings->channels)->toBe(['mail', 'slack']);
    expect($settings->lead_time_days)->toBe(7);
});

test('notification settings can be retrieved for a user', function () {
    $user = User::factory()->create();

    NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail'],
        'lead_time_days' => 14,
    ]);

    $settings = NotificationSetting::forUser($user);

    expect($settings)->not->toBeNull();
    expect($settings->user_id)->toBe($user->id);
    expect($settings->channels)->toBe(['mail']);
    expect($settings->lead_time_days)->toBe(14);
});

test('notification settings returns null when no settings exist for user', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::forUser($user);

    expect($settings)->toBeNull();
});

test('notification settings provides resolved channels correctly', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail', 'slack'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'lead_time_days' => 7,
    ]);

    $resolvedChannels = $settings->resolved_channels;

    expect($resolvedChannels)->toBe(['mail', 'slack']);
});

test('notification settings filters out channels without required configuration', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail', 'slack', 'discord'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'lead_time_days' => 7,
    ]);

    $resolvedChannels = $settings->resolved_channels;

    expect($resolvedChannels)->toBe(['mail', 'slack']); // Discord URL missing
});

test('notification settings provides slack webhook URL correctly', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['slack'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'lead_time_days' => 7,
    ]);

    $webhookUrl = $settings->slackWebhook();

    expect($webhookUrl)->toBe('https://hooks.slack.test/services/TEST/TEST/TESTTOKEN');
});

test('notification settings provides discord webhook URL correctly', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['discord'],
        'discord_webhook_url' => 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890',
        'lead_time_days' => 7,
    ]);

    $webhookUrl = $settings->discordWebhook();

    expect($webhookUrl)->toBe('https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890');
});

test('notification settings provides push endpoint correctly', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['push'],
        'push_endpoint' => 'https://push.example.com/notify',
        'push_token' => 'test-token',
        'lead_time_days' => 7,
    ]);

    $endpoint = $settings->pushEndpoint();

    expect($endpoint)->toBe('https://push.example.com/notify');
});

test('notification settings provides push token correctly', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['push'],
        'push_endpoint' => 'https://push.example.com/notify',
        'push_token' => 'test-token',
        'lead_time_days' => 7,
    ]);

    $token = $settings->pushToken();

    expect($token)->toBe('test-token');
});

test('notification settings validates webhook URLs', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['slack'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'lead_time_days' => 7,
    ]);

    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $validator = new WebhookValidationService;
    $isValid = $validator->validateWebhookUrl($settings->slackWebhook(), 'slack');

    expect($isValid)->toBeTrue();
});

test('notification settings handles invalid webhook URLs', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['slack'],
        'slack_webhook_url' => 'https://example.com/invalid-webhook',
        'lead_time_days' => 7,
    ]);

    $validator = new WebhookValidationService;

    expect(function () use ($settings, $validator) {
        $validator->validateWebhookUrl($settings->slackWebhook(), 'slack');
    })->toThrow(ValidationException::class);
});

test('notification settings can be updated', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    $settings->update([
        'channels' => ['mail', 'slack'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'lead_time_days' => 14,
    ]);

    expect($settings->channels)->toBe(['mail', 'slack']);
    expect($settings->slack_webhook_url)->toBe('https://hooks.slack.test/services/TEST/TEST/TESTTOKEN');
    expect($settings->lead_time_days)->toBe(14);
});

test('notification settings can be deleted', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    $settings->delete();

    expect(NotificationSetting::find($settings->id))->toBeNull();
});

test('notification settings can have preferences', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    $preference = NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'channel' => 'mail',
        'notification_type' => 'event_reminder',
        'enabled' => true,
    ]);

    expect($settings->user->notificationPreferences->modelKeys())->toContain($preference->id);
});

test('notification settings can have templates', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    $template = NotificationTemplate::factory()->create([
        'user_id' => $user->id,
        'channel' => 'mail',
        'notification_type' => 'event_reminder',
        'subject' => 'Test Subject',
        'content' => 'Test Content',
    ]);

    expect($settings->user->notificationTemplates->modelKeys())->toContain($template->id);
});

test('notification settings handles empty channels array', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => [],
        'lead_time_days' => 7,
    ]);

    $resolvedChannels = $settings->resolved_channels;

    expect($resolvedChannels)->toBe([]);
});

test('notification settings handles null webhook URLs', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['slack'],
        'slack_webhook_url' => null,
        'lead_time_days' => 7,
    ]);

    $webhookUrl = $settings->slackWebhook();

    expect($webhookUrl)->toBeNull();
});

test('notification settings handles null push endpoints', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['push'],
        'push_endpoint' => null,
        'push_token' => 'test-token',
        'lead_time_days' => 7,
    ]);

    $endpoint = $settings->pushEndpoint();

    expect($endpoint)->toBeNull();
});

test('notification settings handles null push tokens', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['push'],
        'push_endpoint' => 'https://push.example.com/notify',
        'push_token' => null,
        'lead_time_days' => 7,
    ]);

    $token = $settings->pushToken();

    expect($token)->toBeNull();
});

test('notification settings validates lead time days', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    expect($settings->lead_time_days)->toBeGreaterThan(0);
});

test('notification settings can be created with all channels', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail', 'slack', 'discord', 'push'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'discord_webhook_url' => 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890',
        'push_endpoint' => 'https://push.example.com/notify',
        'push_token' => 'test-token',
        'lead_time_days' => 7,
    ]);

    $resolvedChannels = $settings->resolved_channels;

    expect($resolvedChannels)->toBe(['mail', 'slack', 'discord', 'push']);
});

test('notification settings can be created with only mail channel', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    $resolvedChannels = $settings->resolved_channels;

    expect($resolvedChannels)->toBe(['mail']);
});

test('notification settings can be created with only webhook channels', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['slack', 'discord'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'discord_webhook_url' => 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890',
        'lead_time_days' => 7,
    ]);

    $resolvedChannels = $settings->resolved_channels;

    expect($resolvedChannels)->toBe(['slack', 'discord']);
});

test('notification settings can be created with only push channel', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['push'],
        'push_endpoint' => 'https://push.example.com/notify',
        'push_token' => 'test-token',
        'lead_time_days' => 7,
    ]);

    $resolvedChannels = $settings->resolved_channels;

    expect($resolvedChannels)->toBe(['push']);
});

test('notification settings handles mixed valid and invalid channels', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail', 'slack', 'discord'],
        'slack_webhook_url' => 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN',
        'lead_time_days' => 7,
    ]);

    $resolvedChannels = $settings->resolved_channels;

    expect($resolvedChannels)->toBe(['mail', 'slack']); // Discord URL missing
});

test('notification settings can be used with events', function () {
    $user = User::factory()->create();
    $eventType = EventType::factory()->create();
    $person = Person::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail'],
        'lead_time_days' => 7,
    ]);

    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
    ]);

    expect($event->person)->not->toBeNull();
    expect($event->eventType)->not->toBeNull();
    expect($settings->user)->not->toBeNull();
});

test('notification settings can be created with custom lead time', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail'],
        'lead_time_days' => 30,
    ]);

    expect($settings->lead_time_days)->toBe(30);
});

test('notification settings can be created with minimum lead time', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail'],
        'lead_time_days' => 1,
    ]);

    expect($settings->lead_time_days)->toBe(1);
});

test('notification settings can be created with maximum lead time', function () {
    $user = User::factory()->create();

    $settings = NotificationSetting::factory()->create([
        'user_id' => $user->id,
        'channels' => ['mail'],
        'lead_time_days' => 365,
    ]);

    expect($settings->lead_time_days)->toBe(365);
});
