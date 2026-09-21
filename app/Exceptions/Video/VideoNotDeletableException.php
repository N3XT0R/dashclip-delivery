<?php

declare(strict_types=1);

namespace App\Exceptions\Video;

/**
 * Thrown when a video still has active or picked-up offers at the moment it is about to be deleted.
 */
class VideoNotDeletableException extends VideoException
{
}
