<?php

declare(strict_types=1);

namespace App\Console\Commands\VideoProcessing;

use App\Enum\ProcessingStatusEnum;
use App\Repository\VideoRepository;
use Illuminate\Console\Command;
use Illuminate\Support\LazyCollection;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'video-processing:requeue-stale-running',
    description: <<<'DESCRIPTION'
Requeue videos that have been in the running state for too long (potentially stuck), and are likely to be stale
DESCRIPTION,
)]
class RequeueStaleRunningCommand extends Command
{
    protected function getVideos(VideoRepository $videoRepository): LazyCollection
    {
        return $videoRepository->getLazyForRequeue(
            now()->subHours(2),
            ProcessingStatusEnum::Running,
            chunkSize: 50,
        );
    }

    protected function getErrorLogMessage(): string
    {
        return 'Error requeuing stale running videos';
    }
}
