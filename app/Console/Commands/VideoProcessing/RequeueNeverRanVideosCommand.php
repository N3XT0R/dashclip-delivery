<?php

declare(strict_types=1);

namespace App\Console\Commands\VideoProcessing;

use App\Enum\ProcessingStatusEnum;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'video-processing:requeue-never-ran',
    description: <<<'DESCRIPTION'
Requeue videos that have never been processed (i.e. never ran) and are likely to be stale
DESCRIPTION
)]
class RequeueNeverRanVideosCommand extends AbstractRequeueVideosCommand
{
    protected int $staleAfterHours = 6;

    protected ProcessingStatusEnum $processingStatus = ProcessingStatusEnum::Pending;

    protected string $errorLogMessage = 'Error requeuing never ran videos';
}
