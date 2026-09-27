<?php

declare(strict_types=1);

namespace App\DTO\Import;

/**
 * The settings one clip import runs with, the same for every row of the file.
 */
final readonly class ClipImportSettings
{
    /**
     * @param bool $inferRole derive the camera position from the file name when the row leaves it out
     * @param string $defaultBundle bundle for rows without one
     * @param string $defaultSubmitter submitter for rows without one
     * @param (callable(string):void)|null $onWarning receives every warning of the import
     */
    public function __construct(
        public bool $inferRole,
        public string $defaultBundle,
        public string $defaultSubmitter,
        public mixed $onWarning = null,
    ) {
    }

    /**
     * @return (callable(string):void)|null
     */
    public function onWarning(): ?callable
    {
        return is_callable($this->onWarning) ? $this->onWarning : null;
    }
}
