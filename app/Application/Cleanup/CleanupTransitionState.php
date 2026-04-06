<?php

declare(strict_types=1);

namespace App\Application\Cleanup;

use App\Repository\VideoRepository;
use App\Services\DispatchService;

readonly class CleanupTransitionState
{
    public function __construct(
        private VideoRepository $videoRepository,
        private DispatchService $dispatchService,
    ) {
    }

    public function handle(int $chunkSize = 1000): void
    {
        $videos = $this->videoRepository->getPendingVideosWithHashInTransition(
            chunkSize: $chunkSize,
        );
        foreach ($videos as $video) {
            $this->videoRepository->update($video, [
                'hash' => null,
            ]);

            $this->dispatchService->dispatchVideoQueuedForIngest($video);
        }
    }
}
