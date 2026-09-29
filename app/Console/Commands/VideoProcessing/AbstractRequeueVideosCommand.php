<?php

declare(strict_types=1);

namespace App\Console\Commands\VideoProcessing;

use App\Enum\ProcessingStatusEnum;
use App\Events\Video\VideoQueuedForIngest;
use App\Models\Video;
use App\Repository\VideoRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

abstract class AbstractRequeueVideosCommand extends Command
{
    /** How many videos are read from the database at a time. */
    private const int CHUNK_SIZE = 50;

    /** How long a video may sit in that status before this command takes it on. */
    protected int $staleAfterHours = 1;

    /** The status this command looks at. */
    protected ProcessingStatusEnum $processingStatus = ProcessingStatusEnum::Failed;

    /** What is written to the log when the run goes wrong. */
    protected string $errorLogMessage = 'Error requeuing videos';

    final public function handle(VideoRepository $videoRepository): int
    {
        try {
            /**
             * @var Video $video
             */
            foreach ($this->getVideos($videoRepository) as $video) {
                $disk = $video->getDisk();
                if (!$disk->exists($video->getAttribute('path'))) {
                    $video->delete();
                    Log::warning(
                        'Video file missing during requeue, video deleted',
                        [
                            'video_id' => $video->getKey(),
                            'disk' => $video->getAttribute('disk'),
                            'path' => $video->getAttribute('path')
                        ]
                    );
                } else {
                    VideoQueuedForIngest::dispatch($video);
                }
            }
        } catch (\Throwable $e) {
            Log::error($this->getErrorLogMessage(), [
                'exception' => $e,
            ]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * The videos this command takes on: those that have been sitting in its status for too long.
     */
    protected function getVideos(VideoRepository $videoRepository): LazyCollection
    {
        return $videoRepository->getLazyForRequeue(
            now()->subHours($this->staleAfterHours),
            $this->processingStatus,
            chunkSize: self::CHUNK_SIZE,
        );
    }

    protected function getErrorLogMessage(): string
    {
        return $this->errorLogMessage;
    }
}
