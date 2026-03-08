<?php

declare(strict_types=1);

namespace App\Events\Video;

/**
 * Event that is fired when a video has been stored in the storage and database, and is ready for processing.
 */
readonly class VideoStored extends VideoCreated
{
}