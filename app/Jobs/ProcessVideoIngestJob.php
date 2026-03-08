<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Application\Ingest\Context\IngestContext;
use App\Application\Ingest\IngestPipeline;
use App\Enum\ProcessingStatusEnum;
use App\Repository\VideoRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * This job is responsible for processing the ingest pipeline for a video. It retrieves the video by its ID,
 */
final class ProcessVideoIngestJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $videoId
    ) {
    }

    /**
     * @throws Throwable
     */
    public function handle(
        VideoRepository $videoRepository,
        IngestPipeline $ingestPipeline,
    ): void {
        $video = $videoRepository->findById($this->videoId);

        if (null === $video) {
            return;
        }

        if ($video->processing_status === ProcessingStatusEnum::Deleted->value) {
            return;
        }

        if ($video->processing_status === ProcessingStatusEnum::Completed->value) {
            return;
        }

        $context = new IngestContext(
            video: $video,
        );

        $ingestPipeline->handle($context);
    }
}
