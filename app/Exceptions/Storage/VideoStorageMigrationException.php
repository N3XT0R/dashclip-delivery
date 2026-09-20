<?php

declare(strict_types=1);

namespace App\Exceptions\Storage;

use App\Models\Video;
use Throwable;

/**
 * A video could not be moved to another storage disk; it stays on its current disk.
 */
final class VideoStorageMigrationException extends StorageException
{
    public static function sourceMissing(Video $video): self
    {
        return new self(sprintf('Video %d is missing on disk "%s" at "%s".', $video->getKey(), $video->disk, $video->path));
    }

    public static function unreadable(Video $video): self
    {
        return new self(sprintf('Video %d could not be read from disk "%s".', $video->getKey(), $video->disk));
    }

    public static function incompleteCopy(Video $video, string $target): self
    {
        return new self(sprintf('Video %d was not copied completely to disk "%s".', $video->getKey(), $target));
    }

    public static function unreachable(string $disk, Throwable $previous): self
    {
        return new self(sprintf('Disk "%s" is not reachable.', $disk), previous: $previous);
    }
}
