<?php

declare(strict_types=1);

namespace App\Services\Video;

use App\Enum\Video\DeliveredVersionEnum;
use App\Models\Video;
use App\Notifications\VideoVersionSwitchedNotification;
use App\Repository\VideoRepository;

/**
 * Tells the channels that already downloaded a video that what they hold is no longer the file the
 * submitter passes on.
 */
readonly class DownloadedChannelNotifier
{
    public function __construct(private VideoRepository $videoRepository)
    {
    }

    public function tellAbout(Video $video, DeliveredVersionEnum $delivered): void
    {
        foreach ($this->videoRepository->channelsThatDownloaded($video) as $channel) {
            foreach ($channel->channelUsers as $user) {
                $user->notify(new VideoVersionSwitchedNotification($video, $channel, $delivered));
            }
        }
    }
}
