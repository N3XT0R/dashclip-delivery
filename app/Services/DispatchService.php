<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Video\VideoQueuedForIngest;
use App\Models\Video;
use App\Repository\VideoRepository;

class DispatchService
{
    public function __construct(
        protected VideoRepository $videoRepository,
    ) {
    }

    public function dispatchVideoQueuedForIngest(Video $video): void
    {
        if ($this->videoRepository->exists($video)) {
            return;
        }

        if (!$video->getAttribute('path') || !$video->getAttribute('disk')) {
            return;
        }

        if (!$video->getDisk()->exists($video->getAttribute('path'))) {
            $this->videoRepository->delete($video);
            return;
        }

        VideoQueuedForIngest::dispatch($video);
    }
}
