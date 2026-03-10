<?php

declare(strict_types=1);

namespace App\Console\Commands\VideoProcessing;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'video-processing:requeue-stale-running',
    description: <<<'DESCRIPTION'
Requeue videos that have been in the running state for too long (potentially stuck), and are likely to be stale
DESCRIPTION,
)]
class RequeueStaleRunningCommand extends Command
{

}
