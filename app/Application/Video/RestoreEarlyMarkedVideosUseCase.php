<?php

declare(strict_types=1);

namespace App\Application\Video;

use App\Enum\ProcessingStatusEnum;
use App\Models\Video;
use App\Repository\VideoRepository;
use App\ValueObjects\VideoRestoreResult;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Takes back deletions that were made while a downloaded offer was still running.
 *
 * Until the 4.11.1 fix, a download both ended the offer rounds and marked the video as deleted on
 * the same night, although its channel could still return the offer or fetch it again. This run
 * restores exactly those videos, together with the clips that were deleted with them. A video
 * whose files were already removed for good is left alone and counted as skipped, because
 * restoring it would show an entry without its video.
 */
readonly class RestoreEarlyMarkedVideosUseCase
{
    /**
     * The release that introduced the marking run, so nothing older is ever touched.
     */
    public const string DEFAULT_SINCE = '2026-09-23';

    public function __construct(private VideoRepository $videoRepository)
    {
    }

    /**
     * @param bool $dryRun count the videos that would be restored without restoring them
     * @param CarbonInterface|null $since only videos marked at or after this moment, defaults to
     *                                    the release of the marking run
     * @return VideoRestoreResult
     */
    public function handle(bool $dryRun = false, ?CarbonInterface $since = null): VideoRestoreResult
    {
        $restored = 0;
        $skipped = 0;

        foreach ($this->videoRepository->lazyMarkedDuringOpenDownload($since ?? $this->defaultSince()) as $video) {
            if ($video->processing_status === ProcessingStatusEnum::Deleted) {
                $skipped++;
                continue;
            }

            $restored++;

            if (!$dryRun) {
                $this->restore($video);
            }
        }

        return new VideoRestoreResult($restored, $skipped);
    }

    /**
     * List the videos the run would act on, so an operator can check them before restoring.
     *
     * @param CarbonInterface|null $since
     * @return iterable<int, Video>
     */
    public function candidates(?CarbonInterface $since = null): iterable
    {
        return $this->videoRepository->lazyMarkedDuringOpenDownload($since ?? $this->defaultSince());
    }

    private function restore(Video $video): void
    {
        DB::transaction(function () use ($video): void {
            $this->videoRepository->restoreWithClips($video);
        });
    }

    private function defaultSince(): CarbonInterface
    {
        return Carbon::parse(self::DEFAULT_SINCE);
    }
}
