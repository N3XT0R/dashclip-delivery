<?php

declare(strict_types=1);

namespace App\Application\Cleanup;

use App\Repository\ClipRepository;
use App\Repository\VideoRepository;
use App\Services\VideoService;

readonly class DeleteVideosMissingFromStorage
{
    public function __construct(
        private VideoService $videoService,
        private VideoRepository $videoRepository,
        private ClipRepository $clipRepository,
    ) {
    }

    public function handle(): void
    {
        $missingVideos = $this->videoService->findVideosMissingFromStorage();
        foreach ($missingVideos as $video) {
            $clips = $video->clips;
            foreach ($clips as $clip) {
                $this->clipRepository->delete($clip);
            }


            $this->videoRepository->delete($video);
        }
    }
}
