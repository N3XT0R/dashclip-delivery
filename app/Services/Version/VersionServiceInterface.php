<?php

declare(strict_types=1);

namespace App\Services\Version;

interface VersionServiceInterface
{
    public function getCurrentVersion(): ?string;
}