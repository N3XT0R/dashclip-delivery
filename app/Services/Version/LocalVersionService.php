<?php

declare(strict_types=1);

namespace App\Services\Version;

use Composer\InstalledVersions;

class LocalVersionService implements VersionServiceInterface
{

    /**
     * @var callable|null $fallback
     */
    private $fallback;

    public function __construct(?callable $fallback = null)
    {
        $this->fallback = $fallback;
    }

    public function getCurrentVersion(): ?string
    {
        try {
            $root = InstalledVersions::getRootPackage();
            $package = $root['name'];
            $version = InstalledVersions::getPrettyVersion($package);

            if (is_string($version) && $version !== '') {
                return $version;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        if ($this->fallback !== null) {
            $fallbackVersion = ($this->fallback)();

            return is_string($fallbackVersion) && $fallbackVersion !== '' ? $fallbackVersion : null;
        }

        return null;
    }
}