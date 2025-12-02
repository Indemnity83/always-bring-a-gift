<?php

namespace App\Services\ImageExtractors;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaTagExtractor implements ImageExtractorInterface
{
    /**
     * Meta tag extractor can handle any URL
     */
    public function canHandle(string $url): bool
    {
        return true;
    }

    /**
     * Extract image from Open Graph or Twitter Card meta tags
     */
    public function extractImageUrl(string $url): ?string
    {
        try {
            // Fetch the HTML content with a timeout and follow redirects
            $response = Http::timeout(15)
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 10,
                        'strict' => true,
                        'track_redirects' => true,
                    ],
                ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])
                ->get($url);

            if (! $response->successful()) {
                Log::warning('MetaTagExtractor: Failed to fetch URL', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $html = $response->body();

            // Try Open Graph image first (most common)
            $imageUrl = $this->extractMetaTag($html, 'og:image');

            // Fallback to Twitter Card image
            if (! $imageUrl) {
                $imageUrl = $this->extractMetaTag($html, 'twitter:image');
            }

            // Additional fallback for Twitter Card with :src suffix
            if (! $imageUrl) {
                $imageUrl = $this->extractMetaTag($html, 'twitter:image:src');
            }

            return $imageUrl;
        } catch (\Exception $e) {
            Log::warning('MetaTagExtractor: Failed to extract image', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Meta tags are last resort
     */
    public function getPriority(): int
    {
        return 0;
    }

    /**
     * Extract a meta tag value from HTML
     */
    protected function extractMetaTag(string $html, string $property): ?string
    {
        // Try property="..." format (Open Graph)
        if (preg_match('/<meta[^>]+property=["\']'.$property.'["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            return $matches[1];
        }

        // Try content="..." property="..." format
        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']'.$property.'["\'][^>]*>/i', $html, $matches)) {
            return $matches[1];
        }

        // Try name="..." format (Twitter Cards)
        if (preg_match('/<meta[^>]+name=["\']'.$property.'["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            return $matches[1];
        }

        // Try content="..." name="..." format
        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']'.$property.'["\'][^>]*>/i', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
