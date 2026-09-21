<?php

declare(strict_types=1);

namespace App\Application\Cleanup;

use App\Constants\Config\DefaultConfigEntry;
use App\Facades\Cfg;
use App\Repository\VideoRepository;
use App\ValueObjects\VideoPurgeResult;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Removes videos for good once they have been deleted for longer than the retention period.
 *
 * Deleting a video only marks it as deleted; its file is removed together with the record by the
 * video observer. A video whose file cannot be removed stays marked as deleted and is retried on
 * the next run.
 */
readonly class PurgeDeletedVideosUseCase
{
    private const int DEFAULT_RETENTION_WEEKS = 1;

    public function __construct(private VideoRepository $videoRepository)
    {
    }

    /**
     * @param bool $dryRun count the videos that would be removed without removing them
     * @return VideoPurgeResult
     */
    public function handle(bool $dryRun = false): VideoPurgeResult
    {
        $purged = 0;
        $failed = 0;

        foreach ($this->videoRepository->lazyDeletedBefore(now()->subWeeks($this->retentionWeeks())) as $video) {
            if ($dryRun) {
                $purged++;
                continue;
            }

            try {
                $removed = $this->videoRepository->forceDelete($video);
            } catch (Throwable $exception) {
                Log::error('Deleted video could not be removed for good', [
                    'video_id' => $video->getKey(),
                    'exception' => $exception,
                ]);
                $removed = false;
            }

            $removed ? $purged++ : $failed++;
        }

        return new VideoPurgeResult($purged, $failed);
    }

    private function retentionWeeks(): int
    {
        $weeks = (int)Cfg::get(
            DefaultConfigEntry::POST_EXPIRY_RETENTION_WEEKS,
            'default',
            self::DEFAULT_RETENTION_WEEKS,
            true,
        );

        return max(0, $weeks);
    }
}
