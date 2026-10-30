<?php

declare(strict_types=1);

namespace App\Support\Changelog;

use Illuminate\Support\Facades\File;
use MarkWalet\Changelog\Adapters\FeatureAdapter;
use MarkWalet\Changelog\Adapters\ReleaseAdapter;
use MarkWalet\Changelog\Exceptions\DirectoryNotFoundException;
use MarkWalet\Changelog\Exceptions\FileNotFoundException;
use MarkWalet\Changelog\Exceptions\VersionAlreadyExistsException;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Reads and releases the XML change fragments below the changelog path.
 *
 * Unlike the package adapter it sorts versions semantically (4.12.0 above 4.9.0), keeps the
 * changes in the order they were written and stores the release date next to the fragments.
 */
final class DatedXmlReleaseAdapter implements ReleaseAdapter
{
    public const string UNRELEASED = 'unreleased';

    public const string RELEASE_DATE_FILE = 'release-date';

    public function __construct(private readonly FeatureAdapter $featureAdapter)
    {
    }

    /**
     * Read all fragments of one version.
     *
     * @param string $path
     * @param string $version
     * @return DatedRelease
     * @throws FileNotFoundException When the version folder does not exist.
     */
    public function read(string $path, string $version): DatedRelease
    {
        if ($this->exists($path, $version) === false) {
            throw new FileNotFoundException($path . DIRECTORY_SEPARATOR . $version);
        }

        $versionPath = $path . DIRECTORY_SEPARATOR . $version;
        $release = new DatedRelease($version, $this->releaseDate($versionPath));

        $fragments = collect(File::allFiles($versionPath))
            ->filter(fn (SplFileInfo $file): bool => $file->getExtension() === 'xml');

        foreach ($fragments as $fragment) {
            $release->add($this->featureAdapter->read($fragment->getPathname()));
        }

        return $release;
    }

    /**
     * Move the unreleased fragments into a new version folder dated today.
     *
     * @param string $path
     * @param string $version
     * @return void
     * @throws VersionAlreadyExistsException
     */
    public function release(string $path, string $version): void
    {
        if ($this->exists($path, $version)) {
            throw new VersionAlreadyExistsException($version);
        }

        $unreleasedPath = $path . DIRECTORY_SEPARATOR . self::UNRELEASED;
        $versionPath = $path . DIRECTORY_SEPARATOR . $version;

        File::moveDirectory($unreleasedPath, $versionPath);
        File::delete($versionPath . DIRECTORY_SEPARATOR . '.gitkeep');
        File::put($versionPath . DIRECTORY_SEPARATOR . self::RELEASE_DATE_FILE, now()->toDateString() . PHP_EOL);

        File::makeDirectory($unreleasedPath, 0755, true);
        File::put($unreleasedPath . DIRECTORY_SEPARATOR . '.gitkeep', '');
    }

    /**
     * Read every version, unreleased first, then newest to oldest.
     *
     * @param string $path
     * @return list<DatedRelease>
     * @throws DirectoryNotFoundException
     */
    public function all(string $path): array
    {
        if (is_dir($path) === false) {
            throw new DirectoryNotFoundException($path);
        }

        $versions = array_map(
            static fn (string $directory): string => basename($directory),
            File::directories($path),
        );

        usort($versions, $this->compareVersionsNewestFirst(...));

        return array_map(fn (string $version): DatedRelease => $this->read($path, $version), $versions);
    }

    /**
     * @param string $path
     * @param string $version
     * @return bool
     */
    public function exists(string $path, string $version): bool
    {
        return is_dir($path . DIRECTORY_SEPARATOR . $version);
    }

    private function releaseDate(string $versionPath): ?string
    {
        $dateFile = $versionPath . DIRECTORY_SEPARATOR . self::RELEASE_DATE_FILE;

        return is_file($dateFile) ? trim((string)file_get_contents($dateFile)) : null;
    }

    private function compareVersionsNewestFirst(string $first, string $second): int
    {
        if ($first === self::UNRELEASED || $second === self::UNRELEASED) {
            return ($second === self::UNRELEASED) <=> ($first === self::UNRELEASED);
        }

        return version_compare(ltrim($second, 'vV'), ltrim($first, 'vV'));
    }
}
