<?php

declare(strict_types=1);

namespace App\Console\Commands\VideoProcessing;

use App\Events\Video\VideoQueuedForIngest;
use App\Repository\VideoRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

abstract class AbstractRequeueVideosCommand extends Command
{
    public function handle(VideoRepository $videoRepository): int
    {
        try {
            foreach ($this->getVideos($videoRepository) as $video) {
                VideoQueuedForIngest::dispatch($video);
            }
        } catch (\Throwable $e) {
            Log::error($this->getErrorLogMessage(), [
                'exception' => $e,
            ]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    abstract protected function getVideos(VideoRepository $videoRepository): LazyCollection;

    abstract protected function getErrorLogMessage(): string;
}
