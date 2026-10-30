<?php

declare(strict_types=1);

namespace App\Enum\Changelog;

/**
 * The Keep a Changelog categories, declared in the order they appear within a version.
 */
enum ChangeTypeEnum: string
{
    case ADDED = 'added';
    case CHANGED = 'changed';
    case DEPRECATED = 'deprecated';
    case REMOVED = 'removed';
    case FIXED = 'fixed';
    case SECURITY = 'security';

    /**
     * Return the section heading used in the changelog, e.g. "Added".
     *
     * @return string
     */
    public function heading(): string
    {
        return ucfirst($this->value);
    }
}
