<?php

namespace App\Console\Commands;

use App\Models\NotificationRateLimit;
use App\Services\NotificationRateLimiter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CleanupRateLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup-rate-limits 
                           {--days=7 : Delete rate limit records older than this many days}
                           {--dry-run : Show what would be deleted without actually deleting}
                           {--force : Force cleanup without confirmation}
                           {--cache-only : Only clean up cache entries, not database records}
                           {--database-only : Only clean up database records, not cache entries}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired rate limit records and cache entries';

    /**
     * Execute the console command.
     */
    public function handle(NotificationRateLimiter $rateLimiter): int
    {
        $this->info('Cleaning up rate limit records...');

        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $cacheOnly = $this->option('cache-only');
        $databaseOnly = $this->option('database-only');

        if ($days < 1) {
            $this->error('Days must be at least 1.');

            return Command::FAILURE;
        }

        try {
            $cutoffDate = now()->subDays($days);
            $this->info("Cleaning up records older than {$cutoffDate}");

            if (! $dryRun && ! $force) {
                if (! $this->confirm('This will permanently delete rate limit records. Continue?')) {
                    $this->info('Cleanup cancelled.');

                    return Command::SUCCESS;
                }
            }

            $totalDeleted = 0;

            if (! $databaseOnly) {
                $cacheDeleted = $this->cleanupCacheEntries($rateLimiter, $cutoffDate, $dryRun);
                $totalDeleted += $cacheDeleted;
            }

            if (! $cacheOnly) {
                $dbDeleted = $this->cleanupDatabaseRecords($cutoffDate, $dryRun);
                $totalDeleted += $dbDeleted;
            }

            if ($dryRun) {
                $this->info("DRY RUN: Would delete {$totalDeleted} rate limit records.");
            } else {
                $this->info("Successfully cleaned up {$totalDeleted} rate limit records.");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error cleaning up rate limits: '.$e->getMessage());
            Log::error('Rate limit cleanup failed', ['error' => $e->getMessage()]);

            return Command::FAILURE;
        }
    }

    /**
     * Clean up cache entries.
     */
    protected function cleanupCacheEntries(NotificationRateLimiter $rateLimiter, \Illuminate\Support\Carbon $cutoffDate, bool $dryRun): int
    {
        $this->info('Cleaning up cache entries...');

        $channels = ['mail', 'slack', 'discord', 'push'];
        $deletedCount = 0;

        foreach ($channels as $channel) {
            // Note: In production, you might want to use Redis SCAN for pattern matching
            // For now, we'll use a simplified approach

            $cacheKeys = $this->getCacheKeysForChannel($channel, $cutoffDate);

            foreach ($cacheKeys as $key) {
                if ($dryRun) {
                    $this->line("  Would delete cache key: {$key}");
                } else {
                    Cache::forget($key);
                    $this->line("  Deleted cache key: {$key}", 'verbose');
                }
                $deletedCount++;
            }
        }

        // Also use the rate limiter's built-in cleanup
        $serviceDeleted = $rateLimiter->cleanupExpiredEntries();
        $deletedCount += $serviceDeleted;

        $this->info("Cache entries processed: {$deletedCount}");

        return $deletedCount;
    }

    /**
     * Clean up database records.
     */
    protected function cleanupDatabaseRecords(\Illuminate\Support\Carbon $cutoffDate, bool $dryRun): int
    {
        $this->info('Cleaning up database records...');

        $query = NotificationRateLimit::where('created_at', '<', $cutoffDate);

        $count = $query->count();

        if ($count === 0) {
            $this->info('No expired database records found.');

            return 0;
        }

        if ($dryRun) {
            $this->info("Would delete {$count} database records.");

            // Show sample records that would be deleted
            $sampleRecords = $query->limit(5)->get();
            if ($sampleRecords->count() > 0) {
                $this->info('Sample records that would be deleted:');
                foreach ($sampleRecords as $record) {
                    $this->line("  - User {$record->user_id}, {$record->channel}, created at {$record->created_at}");
                }
            }
        } else {
            $deleted = $query->delete();
            $this->info("Deleted {$deleted} database records.");
        }

        return $count;
    }

    /**
     * Get cache keys for a specific channel that might be expired.
     */
    protected function getCacheKeysForChannel(string $_channel, \Illuminate\Support\Carbon $_cutoffDate): array
    {
        // This is a simplified approach - in production you might want to use Redis SCAN
        // For now, we'll return an empty array and rely on the rate limiter's cleanup

        // In a real implementation, you would:
        // 1. Use Redis SCAN to find all keys matching the pattern
        // 2. Check the TTL of each key
        // 3. Return keys that are expired or older than the cutoff date

        return [];
    }

    /**
     * Show cleanup summary.
     */
    protected function showSummary(int $totalDeleted, bool $dryRun): void
    {
        $this->newLine();
        $this->info('=== Cleanup Summary ===');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No records were actually deleted');
        }

        $this->info("Total records processed: {$totalDeleted}");

        if ($totalDeleted > 0) {
            $this->info('Cleanup completed successfully.');
        } else {
            $this->info('No records required cleanup.');
        }
    }
}
