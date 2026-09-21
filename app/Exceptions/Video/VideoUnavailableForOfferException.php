<?php

declare(strict_types=1);

namespace App\Exceptions\Video;

/**
 * Thrown when an offer is about to be created for a video that was deleted in the meantime.
 */
class VideoUnavailableForOfferException extends VideoException
{
}
