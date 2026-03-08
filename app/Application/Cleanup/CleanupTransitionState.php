<?php

declare(strict_types=1);

namespace App\Application\Cleanup;

use App\Events\Video\VideoQueuedForIngest;
use App\Repository\VideoRepository;

readonly class CleanupTransitionState
{
    public function __construct(
        private VideoRepository $videoRepository
    ) {
    }

    public function handle(): void
    {
        $videos = $this->videoRepository->getPendingVideosWithHashInTransition();
        foreach ($videos as $video) {
            $this->videoRepository->update($video, [
                'hash' => null,
            ]);
            $video->refresh();

            VideoQueuedForIngest::dispatch($video);
        }
    }
}
