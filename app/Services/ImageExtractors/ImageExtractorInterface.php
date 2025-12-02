<?php

namespace App\Services\ImageExtractors;

interface ImageExtractorInterface
{
    /**
     * Check if this extractor can handle the given URL
     */
    public function canHandle(string $url): bool;

    /**
     * Extract an image URL from the given URL
     * The extractor is responsible for fetching any data it needs
     */
    public function extractImageUrl(string $url): ?string;

    /**
     * Get the priority of this extractor (higher = checked first)
     */
    public function getPriority(): int;
}
