<?php

declare(strict_types=1);

namespace App\Application\Cleanup;

use App\Repository\VideoRepository;
use App\Services\VideoService;

readonly class DeleteVideosMissingFromStorage
{
    public function __construct(
        private VideoService $videoService,
        private VideoRepository $videoRepository,
    ) {
    }

    public function handle(): void
    {
        $missingVideos = $this->videoService->findVideosMissingFromStorage();
        foreach ($missingVideos as $video) {
            $this->videoRepository->delete($video);
        }
    }
}
