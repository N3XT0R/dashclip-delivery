<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Changelog\DatedXmlReleaseAdapter;
use App\Support\Changelog\EscapedXmlFeatureAdapter;
use Illuminate\Support\ServiceProvider;
use MarkWalet\Changelog\Adapters\FeatureAdapter;
use MarkWalet\Changelog\Adapters\ReleaseAdapter;
use MarkWalet\Changelog\ChangelogServiceProvider as PackageChangelogServiceProvider;

/**
 * Replaces the adapters of the changelog fragment tooling with the house-style variants.
 *
 * The tooling is a development dependency, so nothing is bound when it is not installed.
 */
class ChangelogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!class_exists(PackageChangelogServiceProvider::class)) {
            return;
        }

        $this->app->singleton(FeatureAdapter::class, EscapedXmlFeatureAdapter::class);
        $this->app->singleton(ReleaseAdapter::class, DatedXmlReleaseAdapter::class);
    }
}
