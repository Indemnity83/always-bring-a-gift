<?php

namespace App\Services;

use App\Services\ImageExtractors\AmazonImageExtractor;
use App\Services\ImageExtractors\ImageExtractorInterface;
use App\Services\ImageExtractors\MetaTagExtractor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LinkPreviewService
{
    /**
     * @var array<ImageExtractorInterface>
     */
    protected array $extractors = [];

    public function __construct()
    {
        // Register extractors (sorted by priority automatically)
        $this->registerExtractor(new MetaTagExtractor);
        $this->registerExtractor(new AmazonImageExtractor);
    }

    /**
     * Register an image extractor
     */
    protected function registerExtractor(ImageExtractorInterface $extractor): void
    {
        $this->extractors[] = $extractor;

        // Sort by priority (highest first)
        usort($this->extractors, fn ($a, $b) => $b->getPriority() <=> $a->getPriority());
    }

    /**
     * Fetch an image from a URL using Open Graph or Twitter Card meta tags
     */
    public function fetchImageFromUrl(string $url): ?string
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
                Log::warning('Failed to fetch URL', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $html = $response->body();

            // Try each extractor in priority order
            $imageUrl = null;
            foreach ($this->extractors as $extractor) {
                if ($extractor->canHandle($url)) {
                    $imageUrl = $extractor->extractImageUrl($url, $html);
                    if ($imageUrl) {
                        Log::info('Found image URL using extractor', [
                            'extractor' => class_basename($extractor),
                            'url' => $url,
                            'imageUrl' => $imageUrl,
                        ]);
                        break;
                    }
                }
            }

            if (! $imageUrl) {
                Log::info('No image found for URL', ['url' => $url]);

                return null;
            }

            // Make image URL absolute if it's relative
            $imageUrl = $this->makeAbsoluteUrl($imageUrl, $url);

            // Download and store the image
            return $this->downloadAndStoreImage($imageUrl);

        } catch (ConnectionException $e) {
            Log::warning('Failed to fetch link preview', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::warning('Failed to process link preview', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Make a URL absolute if it's relative
     */
    protected function makeAbsoluteUrl(string $imageUrl, string $baseUrl): string
    {
        // Already absolute
        if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
            return $imageUrl;
        }

        // Protocol-relative URL
        if (str_starts_with($imageUrl, '//')) {
            $protocol = parse_url($baseUrl, PHP_URL_SCHEME);

            return $protocol.'://'.ltrim($imageUrl, '/');
        }

        // Relative URL
        $parsedBase = parse_url($baseUrl);
        $scheme = $parsedBase['scheme'];
        $host = $parsedBase['host'];

        if (str_starts_with($imageUrl, '/')) {
            return $scheme.'://'.$host.$imageUrl;
        }

        // Relative to current directory
        $path = $parsedBase['path'] ?? '/';
        $directory = dirname($path);

        return $scheme.'://'.$host.$directory.'/'.$imageUrl;
    }

    /**
     * Download an image from a URL and store it
     */
    protected function downloadAndStoreImage(string $imageUrl): ?string
    {
        try {
            $response = Http::timeout(10)->get($imageUrl);

            if (! $response->successful()) {
                return null;
            }

            // Check if it's actually an image
            $contentType = $response->header('Content-Type');
            if (! str_starts_with($contentType, 'image/')) {
                return null;
            }

            // Generate a unique filename
            $extension = $this->getExtensionFromContentType($contentType);
            $filename = 'gifts/'.uniqid().'.'.$extension;

            // Store the image
            Storage::disk('public')->put($filename, $response->body());

            return $filename;

        } catch (\Exception $e) {
            Log::warning('Failed to download image', [
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get file extension from content type
     */
    protected function getExtensionFromContentType(string $contentType): string
    {
        return match (true) {
            str_contains($contentType, 'jpeg'), str_contains($contentType, 'jpg') => 'jpg',
            str_contains($contentType, 'png') => 'png',
            str_contains($contentType, 'gif') => 'gif',
            str_contains($contentType, 'webp') => 'webp',
            default => 'jpg',
        };
    }
}
