<?php

declare(strict_types=1);

namespace App\Console\Commands\VideoProcessing;

use App\Enum\ProcessingStatusEnum;
use App\Repository\VideoRepository;
use Illuminate\Support\LazyCollection;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'video-processing:requeue-never-ran',
    description: <<<'DESCRIPTION'
Requeue videos that have never been processed (i.e. never ran) and are likely to be stale
DESCRIPTION

)]
class RequeueNeverRanVideosCommand extends AbstractRequeueVideosCommand
{
    protected function getVideos(VideoRepository $videoRepository): LazyCollection
    {
        return $videoRepository->getLazyForRequeue(
            now()->subHours(6),
            ProcessingStatusEnum::Pending,
            chunkSize: 50,
        );
    }

    protected function getErrorLogMessage(): string
    {
        return 'Error requeuing never ran videos';
    }
}
