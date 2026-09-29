<?php

declare(strict_types=1);

namespace App\Console\Commands\VideoProcessing;

use App\Enum\ProcessingStatusEnum;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'video-processing:requeue-failed',
    description: 'Requeue failed videos for processing',
)]
class RequeueFailedVideosCommand extends AbstractRequeueVideosCommand
{
    protected int $staleAfterHours = 1;

    protected ProcessingStatusEnum $processingStatus = ProcessingStatusEnum::Failed;

    protected string $errorLogMessage = 'Error requeuing failed videos';
}
