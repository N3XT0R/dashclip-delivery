<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Video\VideoCreatedForIngest;
use App\Jobs\ProcessVideoIngestJob;

class DispatchVideoIngestJobListener
{
    public function handle(VideoCreatedForIngest $event): void
    {
        ProcessVideoIngestJob::dispatch($event->video->getKey());
    }
}
