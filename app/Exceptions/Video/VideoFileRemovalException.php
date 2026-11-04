<?php

declare(strict_types=1);

namespace App\Exceptions\Video;

/**
 * Thrown when a stored file of a video exists but cannot be removed.
 */
class VideoFileRemovalException extends VideoException
{
}
