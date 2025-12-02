<?php

namespace App\Services\ImageExtractors;

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
    public function extractImageUrl(string $url, string $html): ?string
    {
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
    }

    /**
     * Meta tags have highest priority
     */
    public function getPriority(): int
    {
        return 100;
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
