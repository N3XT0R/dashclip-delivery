<?php

declare(strict_types=1);

namespace App\Exceptions\Censor;

use RuntimeException;

/**
 * A video that was to be blurred could not be blurred.
 */
class VideoCensorException extends RuntimeException
{
}
