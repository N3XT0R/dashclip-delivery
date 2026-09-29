<?php

declare(strict_types=1);

namespace App\Exceptions\Video;

use RuntimeException;

/**
 * A video was to hand out its other version, but it has none to hand out.
 */
class VersionNotSwitchableException extends RuntimeException
{
}
