<?php

declare(strict_types=1);

namespace App\Console\Commands\VideoProcessing;

use App\Events\Video\VideoQueuedForIngest;
use App\Models\Video;
use App\Repository\VideoRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

abstract class AbstractRequeueVideosCommand extends Command
{
    final public function handle(VideoRepository $videoRepository): int
    {
        try {
            /**
             * @var Video $video
             */
            foreach ($this->getVideos($videoRepository) as $video) {
                $disk = $video->getDisk();
                if (!$disk->exists($video->getAttribute('path'))) {
                    $video->delete();
                    Log::warning(
                        'Video file missing during requeue, video deleted',
                        [
                            'video_id' => $video->getKey(),
                            'disk' => $video->getAttribute('disk'),
                            'path' => $video->getAttribute('path')
                        ]
                    );
                } else {
                    VideoQueuedForIngest::dispatch($video);
                }
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
