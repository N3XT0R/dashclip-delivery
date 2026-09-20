<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Outcome of moving one video to another storage disk.
 */
enum VideoStorageMigrationResultEnum: string
{
    /** The file was copied to the target and the video switched over. */
    case Copied = 'copied';

    /** A complete copy already existed at the target; only the video was switched. */
    case Switched = 'switched';

    /** Dry run: the file would be copied. */
    case WouldCopy = 'would_copy';

    /** Dry run: a complete copy exists, the video would only be switched. */
    case WouldSwitch = 'would_switch';
}
