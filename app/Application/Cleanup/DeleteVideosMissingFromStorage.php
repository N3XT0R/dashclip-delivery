<?php

declare(strict_types=1);

namespace App\Application\Cleanup;

use App\Services\ClipService;
use App\Services\VideoService;

readonly class DeleteVideosMissingFromStorage
{
    public function __construct(
        private VideoService $videoService,
        private ClipService $clipService,
    ) {
    }

    public function handle(): void
    {
        $missingVideos = $this->videoService->findVideosMissingFromStorage();
        foreach ($missingVideos as $video) {
            $clips = $video->clips;
            foreach ($clips as $clip) {
                $this->clipService->delete($clip);
            }
            
            $this->videoService->delete($video);
        }
    }
}
