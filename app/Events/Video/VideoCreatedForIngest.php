<?php

declare(strict_types=1);

namespace App\Events\Video;

/**
 * This event is fired when a video is created for ingest. It is used to trigger the ingest process.
 */
readonly class VideoCreatedForIngest extends VideoCreated
{
}
