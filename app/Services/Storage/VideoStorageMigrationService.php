<?php

declare(strict_types=1);

namespace App\Services\Storage;

use App\Enum\VideoStorageMigrationResultEnum;
use App\Exceptions\Storage\VideoStorageMigrationException;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Copies both video versions and switches disks only once every copy is complete.
 * Source files stay in place, so a migration can be repeated or rolled back safely.
 */
final readonly class VideoStorageMigrationService
{
    /**
     * Check that a disk answers before any video is touched, so wrong credentials fail once
     * instead of once per video.
     * @throws VideoStorageMigrationException When the disk cannot be reached.
     */
    public function assertReachable(string $disk): void
    {
        try {
            Storage::disk($disk)->exists('.connectivity-check');
        } catch (Throwable $exception) {
            throw VideoStorageMigrationException::unreachable($disk, $exception);
        }
    }

    /**
     * @throws VideoStorageMigrationException When the source is missing or the copy is incomplete.
     */
    public function migrate(Video $video, string $target, bool $dryRun = false): VideoStorageMigrationResultEnum
    {
        $alreadyCopied = true;
        try {
            foreach ($video->storedFilePaths() as $path) {
                if (!$this->copyFile($video, $path, $target, $dryRun)) {
                    $alreadyCopied = false;
                }
            }
        } catch (VideoStorageMigrationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw VideoStorageMigrationException::incompleteCopy($video, $target, $exception);
        }

        if ($dryRun) {
            return $alreadyCopied ? VideoStorageMigrationResultEnum::WouldSwitch : VideoStorageMigrationResultEnum::WouldCopy;
        }

        $video->forceFill(['disk' => $target])->save();

        return $alreadyCopied ? VideoStorageMigrationResultEnum::Switched : VideoStorageMigrationResultEnum::Copied;
    }

    /** Return whether this file was already complete at the destination. */
    private function copyFile(Video $video, string $path, string $target, bool $dryRun): bool
    {
        $source = $video->getDisk();
        if (!$source->exists($path)) {
            throw VideoStorageMigrationException::sourceMissing($video);
        }

        $targetDisk = Storage::disk($target);
        $size = $source->size($path);
        $alreadyCopied = $targetDisk->exists($path) && $targetDisk->size($path) === $size;

        if ($dryRun) {
            return $alreadyCopied;
        }

        if (!$alreadyCopied) {
            $stream = $source->readStream($path);
            if (!is_resource($stream)) {
                throw VideoStorageMigrationException::unreadable($video);
            }
            try {
                $written = $targetDisk->writeStream($path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
            if ($written === false || $targetDisk->size($path) !== $size) {
                throw VideoStorageMigrationException::incompleteCopy($video, $target);
            }
        }

        return $alreadyCopied;
    }
}
