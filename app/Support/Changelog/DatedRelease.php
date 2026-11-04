<?php

declare(strict_types=1);

namespace App\Support\Changelog;

use MarkWalet\Changelog\Change;
use MarkWalet\Changelog\Feature;
use MarkWalet\Changelog\Release;

/**
 * A release that keeps its changes in the order they were written and knows its release date.
 */
final class DatedRelease extends Release
{
    /**
     * @var list<Change>
     */
    private array $orderedChanges = [];

    /**
     * @param string $version
     * @param string|null $releasedOn Release date as Y-m-d, null while the version is unreleased.
     */
    public function __construct(string $version, private readonly ?string $releasedOn = null)
    {
        parent::__construct($version);
    }

    /**
     * Append the changes of the given feature without re-sorting them.
     *
     * @param Feature $feature
     * @return void
     */
    public function add(Feature $feature): void
    {
        foreach ($feature->changes() as $change) {
            $this->orderedChanges[] = $change;
        }
    }

    /**
     * @return list<Change>
     */
    public function changes(): array
    {
        return $this->orderedChanges;
    }

    /**
     * @return string|null Release date as Y-m-d, null while the version is unreleased.
     */
    public function releasedOn(): ?string
    {
        return $this->releasedOn;
    }
}
