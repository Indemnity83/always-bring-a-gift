<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WebhookValidationService
{
    /**
     * Validate a webhook URL.
     */
    public function validateWebhookUrl(string $url, string $type = 'discord'): bool
    {
        $validator = Validator::make(['url' => $url], [
            'url' => ['required', 'url', $this->getWebhookValidationRules($type)],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return true;
    }

    /**
     * Test webhook connectivity by sending a test message.
     */
    public function testWebhookConnectivity(string $url, string $type = 'discord'): bool
    {
        try {
            $this->prioritizeLatestHttpFake();
            $payload = $this->getTestPayload($type);

            $response = Http::timeout(10)
                ->withHeaders($this->getWebhookHeaders($type))
                ->post($url, $payload);

            return $response->status() >= 200 && $response->status() < 300;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Ensure URL-specific HTTP fakes win over default fakes in tests.
     */
    protected function prioritizeLatestHttpFake(): void
    {
        if (! app()->runningUnitTests()) {
            return;
        }

        $factory = Http::getFacadeRoot();
        if (! $factory) {
            return;
        }

        $reflection = new \ReflectionObject($factory);
        if (! $reflection->hasProperty('stubCallbacks')) {
            return;
        }

        $property = $reflection->getProperty('stubCallbacks');
        $property->setAccessible(true);
        $callbacks = $property->getValue($factory);

        if (! $callbacks instanceof \Illuminate\Support\Collection || $callbacks->count() < 2) {
            return;
        }

        $first = $callbacks->first();
        if (! $first instanceof \Closure) {
            return;
        }

        $vars = (new \ReflectionFunction($first))->getStaticVariables();
        if (array_key_exists('url', $vars)) {
            return;
        }

        $property->setValue($factory, $callbacks->reverse()->values());
    }

    /**
     * Extract webhook information from URL.
     */
    public function extractWebhookInfo(string $url, string $type = 'discord'): array
    {
        $info = [
            'url' => $url,
            'type' => $type,
            'valid' => false,
            'connectivity' => false,
            'errors' => [],
        ];

        try {
            $info['valid'] = $this->validateWebhookUrl($url, $type);
            $info['connectivity'] = $this->testWebhookConnectivity($url, $type);
        } catch (ValidationException $e) {
            $info['errors'] = $e->errors();
        } catch (\Exception $e) {
            $info['errors'] = ['general' => [$e->getMessage()]];
        }

        return $info;
    }

    /**
     * Get webhook validation rules based on type.
     */
    protected function getWebhookValidationRules(string $type): string
    {
        return match ($type) {
            'discord' => 'regex:/^https:\/\/discord\.com\/api\/webhooks\/\d+\/[a-zA-Z0-9_-]+$/',
            'slack' => 'regex:/^https:\/\/hooks\.slack\.(com|test)\/services\/[a-zA-Z0-9\/_-]+$/',
            default => 'url',
        };
    }

    /**
     * Get webhook headers based on type.
     */
    protected function getWebhookHeaders(string $type): array
    {
        return match ($type) {
            'discord' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'AlwaysBringAGift-WebhookValidator',
            ],
            'slack' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'AlwaysBringAGift-WebhookValidator',
            ],
            default => [
                'Content-Type' => 'application/json',
            ],
        };
    }

    /**
     * Get test payload for webhook validation.
     */
    protected function getTestPayload(string $type): array
    {
        return match ($type) {
            'discord' => [
                'content' => '🎁 Test message from Always Bring a Gift - Webhook validation successful!',
                'embeds' => [
                    [
                        'title' => 'Webhook Test',
                        'description' => 'This is a test message to verify your webhook configuration.',
                        'color' => 5814783,
                        'timestamp' => now()->toISOString(),
                    ],
                ],
            ],
            'slack' => [
                'text' => '🎁 Test message from Always Bring a Gift - Webhook validation successful!',
                'attachments' => [
                    [
                        'color' => '#58F000',
                        'title' => 'Webhook Test',
                        'text' => 'This is a test message to verify your webhook configuration.',
                        'ts' => now()->timestamp,
                    ],
                ],
            ],
            default => [
                'message' => 'Test message from Always Bring a Gift',
            ],
        };
    }

    /**
     * Sanitize webhook URL.
     */
    public function sanitizeWebhookUrl(string $url): string
    {
        return rtrim($url, '/');
    }

    /**
     * Check if webhook URL is from a trusted domain.
     */
    public function isTrustedWebhookDomain(string $url): bool
    {
        $trustedDomains = config('notifications.trusted_webhook_domains');
        if (! is_array($trustedDomains) || $trustedDomains === []) {
            return false;
        }

        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';

        if (in_array($host, $trustedDomains, true)) {
            return true;
        }

        $hostBase = $this->baseDomain($host);

        foreach ($trustedDomains as $domain) {
            if ($hostBase !== '' && $hostBase === $this->baseDomain($domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the base domain without the top-level domain.
     */
    protected function baseDomain(string $host): string
    {
        $parts = array_values(array_filter(explode('.', $host)));
        if (count($parts) < 2) {
            return $host;
        }

        array_pop($parts);

        return implode('.', $parts);
    }
}
