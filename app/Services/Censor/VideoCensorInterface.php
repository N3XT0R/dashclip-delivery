<?php

declare(strict_types=1);

namespace App\Services\Censor;

use App\Exceptions\Censor\VideoCensorException;
use App\ValueObjects\CensorResult;

interface VideoCensorInterface
{
    /**
     * Whether this installation can blur videos at all.
     */
    public function isAvailable(): bool;

    /**
     * Write a copy of the video with every number plate blurred.
     *
     * @param string $sourcePath readable path of the video to work on
     * @param string $targetPath where the blurred copy is written
     * @return CensorResult
     * @throws VideoCensorException when the video could not be blurred
     */
    public function censor(string $sourcePath, string $targetPath): CensorResult;
}
