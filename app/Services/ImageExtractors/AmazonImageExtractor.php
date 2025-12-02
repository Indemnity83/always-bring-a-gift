<?php

namespace App\Services\ImageExtractors;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmazonImageExtractor implements ImageExtractorInterface
{
    /**
     * Check if this extractor can handle Amazon URLs
     */
    public function canHandle(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host && (
            str_contains($host, 'amazon.com') ||
            str_contains($host, 'amazon.co.uk') ||
            str_contains($host, 'amazon.ca') ||
            str_contains($host, 'amazon.de') ||
            str_contains($host, 'amazon.fr') ||
            str_contains($host, 'amazon.it') ||
            str_contains($host, 'amazon.es') ||
            str_contains($host, 'amazon.co.jp') ||
            str_contains($host, 'a.co')
        );
    }

    /**
     * Extract Amazon product image using OpenWeb Ninja API
     */
    public function extractImageUrl(string $url): ?string
    {
        $apiKey = config('services.openweb_ninja.key');

        if (! $apiKey) {
            Log::warning('OpenWeb Ninja API key not configured');

            return null;
        }

        // For short links (a.co), follow redirects to get the full URL
        $finalUrl = $this->resolveShortLink($url);

        // Extract ASIN from the URL
        $asin = $this->extractAsin($finalUrl);

        if (! $asin) {
            Log::info('Could not extract ASIN from Amazon URL', ['url' => $url, 'finalUrl' => $finalUrl]);

            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
            ])->get('https://api.openwebninja.com/realtime-amazon-data/product-details', [
                'asin' => $asin,
                'country' => 'US',
                'language' => 'en_US',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['data']['product_photo'] ?? null;
            }

            Log::warning('OpenWeb Ninja API request failed', [
                'status' => $response->status(),
                'asin' => $asin,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching Amazon product image from OpenWeb Ninja', [
                'message' => $e->getMessage(),
                'asin' => $asin,
            ]);

            return null;
        }
    }

    /**
     * Resolve short links by following redirects
     */
    protected function resolveShortLink(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        // Only resolve for a.co short links
        if (! str_contains($host, 'a.co')) {
            return $url;
        }

        try {
            // Use GET request with User-Agent to follow redirects
            // Amazon short links require GET (HEAD returns 404)
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                ])
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 10,
                        'strict' => true,
                        'track_redirects' => true,
                    ],
                ])
                ->get($url);

            // Get the final URL from handler stats
            $effectiveUrl = $response->handlerStats()['url'] ?? null;

            if ($effectiveUrl && $effectiveUrl !== $url) {
                return $effectiveUrl;
            }

            return $url;
        } catch (\Exception $e) {
            Log::warning('Failed to resolve Amazon short link', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return $url;
        }
    }

    /**
     * Extract ASIN from Amazon URL
     */
    protected function extractAsin(string $url): ?string
    {
        // Pattern 1: /dp/ASIN
        if (preg_match('/\/dp\/([A-Z0-9]{10})/i', $url, $matches)) {
            return $matches[1];
        }

        // Pattern 2: /gp/product/ASIN
        if (preg_match('/\/gp\/product\/([A-Z0-9]{10})/i', $url, $matches)) {
            return $matches[1];
        }

        // Pattern 3: /product/ASIN (less common)
        if (preg_match('/\/product\/([A-Z0-9]{10})/i', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Amazon extractor has lower priority than meta tags
     */
    public function getPriority(): int
    {
        return 50;
    }
}
