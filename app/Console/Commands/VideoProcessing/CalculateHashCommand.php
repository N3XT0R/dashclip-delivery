<?php

declare(strict_types=1);

namespace App\Console\Commands\VideoProcessing;

use App\Application\Video\UpdateVideoHash;
use App\Repository\VideoRepository;
use Illuminate\Console\Command;

class CalculateHashCommand extends Command
{

    protected $signature = 'video:calculate-hash';

    public function handle(
        VideoRepository $videoRepository,
        UpdateVideoHash $updateVideoHash
    ): void {
        $videos = $videoRepository->getVideosWhereHashIsEmpty();
        foreach ($videos as $video) {
            $updateVideoHash->handle($video);
        }
    }
}