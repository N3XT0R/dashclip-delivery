<?php

declare(strict_types=1);

namespace App\Application\Cleanup;

use App\Constants\Config\DefaultConfigEntry;
use App\Enum\ProcessingStatusEnum;
use App\Facades\Cfg;
use App\Repository\VideoRepository;
use App\Services\VideoService;
use App\ValueObjects\VideoPurgeResult;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Removes the stored files of videos that have been deleted for longer than the retention period.
 *
 * The video row, its clips, offers and downloads stay as history; processing_status "deleted" marks
 * that the files are gone, so every video is handled once. A video whose files cannot be removed
 * keeps its status and is retried on the next run.
 */
readonly class PurgeDeletedVideosUseCase
{
    private const int DEFAULT_RETENTION_WEEKS = 4;

    public function __construct(private VideoRepository $videoRepository, private VideoService $videoService)
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
                $this->videoService->removeStoredFiles($video);
                if (!$this->videoRepository->updateProcessingStatus($video, ProcessingStatusEnum::Deleted)) {
                    Log::error('Processing status of a deleted video could not be updated', [
                        'video_id' => $video->getKey(),
                    ]);
                    $failed++;
                    continue;
                }
                $purged++;
            } catch (Throwable $exception) {
                Log::error('Files of a deleted video could not be removed', [
                    'video_id' => $video->getKey(),
                    'exception' => $exception,
                ]);
                $failed++;
            }
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
