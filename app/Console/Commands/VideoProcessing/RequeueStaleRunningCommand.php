<?php

declare(strict_types=1);

namespace App\Console\Commands\VideoProcessing;

use App\Enum\ProcessingStatusEnum;
use App\Events\Video\VideoQueuedForIngest;
use App\Repository\VideoRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'video-processing:requeue-stale-running',
    description: <<<'DESCRIPTION'
Requeue videos that have been in the running state for too long (potentially stuck), and are likely to be stale
DESCRIPTION,
)]
class RequeueStaleRunningCommand extends Command
{
    public function handle(VideoRepository $videoRepository): int
    {
        try {
            $videos = $videoRepository->getLazyForRequeue(
                now()->subHours(2),
                ProcessingStatusEnum::Failed,
                chunkSize: 100,
            );
            foreach ($videos as $video) {
                VideoQueuedForIngest::dispatch($video);
            }
        } catch (\Throwable $e) {
            Log::error('Error requeuing failed videos: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return self::SUCCESS;
    }
}
