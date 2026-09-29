<?php

declare(strict_types=1);

namespace App\Exceptions\Censor;

use App\Exceptions\Video\VideoException;

/**
 * A video that was to be blurred could not be blurred.
 */
class VideoCensorException extends VideoException
{
}
