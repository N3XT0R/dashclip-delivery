<?php

declare(strict_types=1);

namespace App\Events\Video;

/**
 * This event is fired when a video is queued for ingest.
 * It extends the VideoCreated event, which means it will have all the same properties and methods as VideoCreated,
 * but it can be used to specifically indicate that the video is now in the ingest queue.
 */
class VideoQueuedForIngest extends VideoCreated
{
}
