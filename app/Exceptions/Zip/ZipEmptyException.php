<?php

declare(strict_types=1);

namespace App\Exceptions\Zip;

/**
 * Every offered video was skipped, so there is no archive to deliver.
 */
final class ZipEmptyException extends ZipBuildException
{
    public static function allSkipped(): self
    {
        return new self('None of the offered videos could be added to the ZIP archive.');
    }
}
