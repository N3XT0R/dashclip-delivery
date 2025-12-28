<?php

declare(strict_types=1);

namespace App\Application\Video;

use App\Models\Video;
use App\Services\VideoService;

readonly class AssignVideoToTeam
{
    public function __construct(private VideoService $videoService)
    {
    }

    public function handle(Video $video): void
    {
        $this->videoService->assignVideoToOwnTeam($video);
    }
}
