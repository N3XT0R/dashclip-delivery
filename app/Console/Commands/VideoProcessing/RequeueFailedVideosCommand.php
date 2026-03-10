<?php

declare(strict_types=1);

namespace App\Console\Commands\VideoProcessing;

use App\Enum\ProcessingStatusEnum;
use App\Repository\VideoRepository;
use Illuminate\Support\LazyCollection;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'video-processing:requeue-failed',
    description: 'Requeue failed videos for processing',
)]
class RequeueFailedVideosCommand extends AbstractRequeueVideosCommand
{
    protected function getVideos(VideoRepository $videoRepository): LazyCollection
    {
        return $videoRepository->getLazyForRequeue(
            now()->subHour(),
            ProcessingStatusEnum::Failed,
            chunkSize: 50,
        );
    }

    protected function getErrorLogMessage(): string
    {
        return 'Error requeuing failed videos';
    }

}
