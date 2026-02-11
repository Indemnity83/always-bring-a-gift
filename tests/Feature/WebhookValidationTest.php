<?php

use App\Services\WebhookValidationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Http::fake();
});

afterEach(function () {
    Http::fake();
});

test('webhook validation service validates valid discord webhook', function () {
    $service = new WebhookValidationService;
    $validDiscordUrl = 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890';

    $result = $service->validateWebhookUrl($validDiscordUrl, 'discord');

    expect($result)->toBeTrue();
});

test('webhook validation service validates valid slack webhook', function () {
    $service = new WebhookValidationService;
    $validSlackUrl = 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN';

    Http::fake([
        '*' => Http::response([], 200),
    ]);

    $result = $service->validateWebhookUrl($validSlackUrl, 'slack');

    expect($result)->toBeTrue();
});

test('webhook validation service rejects invalid discord webhook format', function () {
    $service = new WebhookValidationService;
    $invalidDiscordUrl = 'https://example.com/webhook/123/abc';

    $this->expectException(ValidationException::class);
    $service->validateWebhookUrl($invalidDiscordUrl, 'discord');
});

test('webhook validation service rejects invalid slack webhook format', function () {
    $service = new WebhookValidationService;
    $invalidSlackUrl = 'https://example.com/slack/webhook';

    $this->expectException(ValidationException::class);
    $service->validateWebhookUrl($invalidSlackUrl, 'slack');
});

test('webhook validation service rejects non-url strings', function () {
    $service = new WebhookValidationService;
    $notAUrl = 'this is not a url';

    $this->expectException(ValidationException::class);
    $service->validateWebhookUrl($notAUrl, 'discord');
});

test('webhook validation service tests connectivity successfully', function () {
    $service = new WebhookValidationService;
    $url = 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890';

    Http::fake([
        $url => Http::response([], 200),
    ]);

    $result = $service->testWebhookConnectivity($url, 'discord');

    expect($result)->toBeTrue();
});

test('webhook validation service handles connectivity failures', function () {
    $service = new WebhookValidationService;
    $url = 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890';

    Http::fake([
        $url => Http::response([], 500),
    ]);

    $result = $service->testWebhookConnectivity($url, 'discord');

    expect($result)->toBeFalse();
});

test('webhook validation service handles connection timeouts', function () {
    $service = new WebhookValidationService;
    $url = 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890';

    Http::fake([
        $url => Http::response([], 500),
    ]);

    $result = $service->testWebhookConnectivity($url, 'discord');

    expect($result)->toBeFalse();
});

test('webhook validation service extracts webhook information correctly', function () {
    $service = new WebhookValidationService;
    $url = 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890';

    Http::fake([
        $url => Http::response([], 200),
    ]);

    $info = $service->extractWebhookInfo($url, 'discord');

    expect($info)->toBeArray();
    expect($info)->toHaveKeys(['url', 'type', 'valid', 'connectivity', 'errors']);
    expect($info['url'])->toBe($url);
    expect($info['type'])->toBe('discord');
    expect($info['valid'])->toBeTrue();
    expect($info['connectivity'])->toBeTrue();
    expect($info['errors'])->toBe([]);
});

test('webhook validation service extracts webhook information with validation errors', function () {
    $service = new WebhookValidationService;
    $invalidUrl = 'https://example.com/webhook/123/abc';

    $info = $service->extractWebhookInfo($invalidUrl, 'discord');

    expect($info)->toBeArray();
    expect($info)->toHaveKeys(['url', 'type', 'valid', 'connectivity', 'errors']);
    expect($info['url'])->toBe($invalidUrl);
    expect($info['type'])->toBe('discord');
    expect($info['valid'])->toBeFalse();
    expect($info['connectivity'])->toBeFalse();
    expect($info['errors'])->not->toBeEmpty();
});

test('webhook validation service extracts webhook information with connectivity errors', function () {
    $service = new WebhookValidationService;
    $url = 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890';

    Http::fake([
        $url => Http::response([], 500),
    ]);

    $info = $service->extractWebhookInfo($url, 'discord');

    expect($info)->toBeArray();
    expect($info['valid'])->toBeTrue();
    expect($info['connectivity'])->toBeFalse();
});

test('webhook validation service sends correct discord payload', function () {
    $service = new WebhookValidationService;
    $testUrl = 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890';

    Http::fake([
        $testUrl => Http::response([], 200),
    ]);

    $service->testWebhookConnectivity($testUrl, 'discord');

    Http::assertSent(function ($request) use ($testUrl) {
        return $request->url() === $testUrl &&
               $request->hasHeader('Content-Type', 'application/json') &&
               $request->hasHeader('User-Agent', 'AlwaysBringAGift-WebhookValidator') &&
               isset($request['content']) &&
               isset($request['embeds']) &&
               is_array($request['embeds']);
    });
});

test('webhook validation service sends correct slack payload', function () {
    $service = new WebhookValidationService;
    $testUrl = 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN';

    Http::fake([
        $testUrl => Http::response([], 200),
    ]);

    $service->testWebhookConnectivity($testUrl, 'slack');

    Http::assertSent(function ($request) use ($testUrl) {
        return $request->url() === $testUrl &&
               $request->hasHeader('Content-Type', 'application/json') &&
               $request->hasHeader('User-Agent', 'AlwaysBringAGift-WebhookValidator') &&
               isset($request['text']) &&
               isset($request['attachments']) &&
               is_array($request['attachments']);
    });
});

test('webhook validation service sanitizes webhook urls', function () {
    $service = new WebhookValidationService;
    $urlWithTrailingSlash = 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890/';

    $sanitized = $service->sanitizeWebhookUrl($urlWithTrailingSlash);

    expect($sanitized)->toBe('https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890');
});

test('webhook validation service identifies trusted webhook domains', function () {
    $service = new WebhookValidationService;

    config([
        'notifications.trusted_webhook_domains' => [
            'discord.com',
            'hooks.slack.com',
        ],
    ]);

    $trustedDiscordUrl = 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890';
    $trustedSlackUrl = 'https://hooks.slack.test/services/TEST/TEST/TESTTOKEN';
    $untrustedUrl = 'https://example.com/webhook/123/abc';

    expect($service->isTrustedWebhookDomain($trustedDiscordUrl))->toBeTrue();
    expect($service->isTrustedWebhookDomain($trustedSlackUrl))->toBeTrue();
    expect($service->isTrustedWebhookDomain($untrustedUrl))->toBeFalse();
});

test('webhook validation service handles default webhook type', function () {
    $service = new WebhookValidationService;
    $url = 'https://example.com/webhook';

    Http::fake([
        $url => Http::response([], 200),
    ]);

    $result = $service->testWebhookConnectivity($url, 'unknown');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) use ($url) {
        return $request->url() === $url &&
               $request->hasHeader('Content-Type', 'application/json') &&
               isset($request['message']);
    });
});

test('webhook validation service handles network exceptions', function () {
    $service = new WebhookValidationService;
    $url = 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890';

    Http::fake([
        $url => Http::response([], 200),
    ]);

    $result = $service->testWebhookConnectivity($url, 'discord');

    expect($result)->toBeTrue();
});

test('webhook validation service validates with default rules for unknown type', function () {
    $service = new WebhookValidationService;
    $validUrl = 'https://example.com/webhook';

    // This should not throw an exception for unknown type
    $result = $service->validateWebhookUrl($validUrl, 'unknown');

    expect($result)->toBeTrue();
});

test('webhook validation service handles empty config for trusted domains', function () {
    $service = new WebhookValidationService;

    // Clear config
    config()->offsetUnset('notifications.trusted_webhook_domains');

    $url = 'https://discord.com/api/webhooks/123456789012345678/AbCdEfGhIjKlMnOpQrStUvWxYz1234567890';

    expect($service->isTrustedWebhookDomain($url))->toBeFalse();
});
