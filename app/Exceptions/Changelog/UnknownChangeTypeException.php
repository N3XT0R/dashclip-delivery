<?php

declare(strict_types=1);

namespace App\Exceptions\Changelog;

final class UnknownChangeTypeException extends ChangelogException
{
    /**
     * @param string $type
     * @param string $version
     * @return self
     */
    public static function forType(string $type, string $version): self
    {
        return new self(sprintf(
            'Change type "%s" in version "%s" is not one of added, changed, deprecated, removed, fixed, security.',
            $type,
            $version,
        ));
    }
}
