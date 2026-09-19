<?php

declare(strict_types=1);

namespace App\Exceptions\Zip;

/**
 * A single offered video could not be packed, so it is left out while the rest of the archive is built.
 */
final class ZipEntrySkippedException extends ZipException
{
    private function __construct(string $message, public readonly string $reason)
    {
        parent::__construct($message);
    }

    public static function videoDeleted(): self
    {
        return new self('The offered video no longer exists.', 'video_deleted');
    }

    public static function fileMissing(): self
    {
        return new self('The offered video file is missing.', 'file_missing');
    }

    public static function unreadable(): self
    {
        return new self('The offered video file could not be read.', 'file_unreadable');
    }

    public static function incompleteTransfer(): self
    {
        return new self('The remote video transfer was incomplete.', 'incomplete_transfer');
    }

    public static function notAdded(): self
    {
        return new self('The offered video could not be added to the ZIP archive.', 'zip_add_failed');
    }
}
