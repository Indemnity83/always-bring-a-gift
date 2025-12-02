<?php

namespace App\Services\ImageExtractors;

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
     * Extract Amazon product image from HTML
     *
     * Currently disabled - Amazon blocks bot scraping and it violates TOS.
     * TODO: Implement proper Amazon Product Advertising API integration
     */
    public function extractImageUrl(string $url, string $html): ?string
    {
        // Return null to skip Amazon image extraction until API is implemented
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
