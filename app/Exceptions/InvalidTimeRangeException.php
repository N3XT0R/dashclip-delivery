<?php

declare(strict_types=1);

namespace App\Exceptions;

use InvalidArgumentException;

final class InvalidTimeRangeException extends InvalidArgumentException
{
    public function __construct(int $start, int $end)
    {
        parent::__construct("Invalid time range: start={$start}, end={$end}");
    }
}