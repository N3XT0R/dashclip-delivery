<?php

declare(strict_types=1);

namespace App\Console\Commands\VideoProcessing;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'video-processing:requeue-failed',
    description: 'Requeue failed videos for processing',
)]
class RequeueFailedVideosCommand extends Command
{

    public function handle(): void
    {
    }
}
