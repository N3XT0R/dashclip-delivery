<?php

declare(strict_types=1);

namespace App\Console\Commands\VideoProcessing;

use App\Application\Video\LookupAndUpdateVideoHash;
use App\Repository\VideoRepository;
use Illuminate\Console\Command;

class ProcessVideosCommand extends Command
{

    protected $signature = 'video:process-videos';

    public function handle(
        VideoRepository $videoRepository,
        LookupAndUpdateVideoHash $lookupAndUpdateVideoHash
    ): void {
        $videos = $videoRepository->getVideosWhereHashIsEmpty();
        foreach ($videos as $video) {
            $lookupAndUpdateVideoHash->handle($video);
        }
    }
}