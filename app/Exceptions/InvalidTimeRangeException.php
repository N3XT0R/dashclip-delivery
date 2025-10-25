<?php

declare(strict_types=1);

namespace App\Exceptions;

use InvalidArgumentException;

final class InvalidTimeRangeException extends InvalidArgumentException
{
    public function __construct(
        public readonly int $start,
        public readonly int $end
    ) {
        parent::__construct("Invalid time range: start={$start}, end={$end}");
    }

    public function context(): array
    {
        return [
            'start' => $this->start,
            'end' => $this->end,
            'duration' => $this->end - $this->start,
        ];
    }
}