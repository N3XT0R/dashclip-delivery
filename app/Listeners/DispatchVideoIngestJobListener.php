<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Video\VideoQueuedForIngest;
use App\Jobs\ProcessVideoIngestJob;

/**
 * This listener is responsible for dispatching the ProcessVideoIngestJob when a video is created for ingest.
 */
class DispatchVideoIngestJobListener
{
    public function handle(VideoQueuedForIngest $event): void
    {
        ProcessVideoIngestJob::dispatch($event->video->getKey());
    }
}
