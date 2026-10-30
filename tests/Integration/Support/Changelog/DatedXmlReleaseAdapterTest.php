<?php

declare(strict_types=1);

namespace Tests\Integration\Support\Changelog;

use App\Support\Changelog\DatedRelease;
use App\Support\Changelog\DatedXmlReleaseAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MarkWalet\Changelog\Adapters\FeatureAdapter;
use MarkWalet\Changelog\Adapters\ReleaseAdapter;
use MarkWalet\Changelog\Change;
use MarkWalet\Changelog\Exceptions\VersionAlreadyExistsException;
use MarkWalet\Changelog\Feature;
use Tests\TestCase;

/**
 * The fragments below .changes/ are read and released in the order CHANGELOG.md needs.
 */
final class DatedXmlReleaseAdapterTest extends TestCase
{
    private string $root;

    private ReleaseAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/changelog-' . Str::uuid());
        $this->adapter = $this->app->make(ReleaseAdapter::class);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function testContainerResolvesTheHouseStyleAdapter(): void
    {
        $this->assertInstanceOf(DatedXmlReleaseAdapter::class, $this->adapter);
    }

    public function testListsUnreleasedFirstAndVersionsSemanticallyNewestFirst(): void
    {
        foreach (['4.9.0', 'unreleased', '4.12.0', '4.0.0-beta.9', '4.0.0'] as $version) {
            File::ensureDirectoryExists($this->root . '/' . $version);
        }

        $versions = array_map(
            static fn (DatedRelease $release): string => $release->version(),
            $this->adapter->all($this->root),
        );

        $this->assertSame(['unreleased', '4.12.0', '4.9.0', '4.0.0', '4.0.0-beta.9'], $versions);
    }

    public function testReadsTheReleaseDateAndKeepsTheWrittenOrder(): void
    {
        $this->writeFeature('4.12.2/history.xml', [
            new Change('fixed', 'Zeta'),
            new Change('fixed', 'Alpha'),
        ]);
        File::put($this->root . '/4.12.2/' . DatedXmlReleaseAdapter::RELEASE_DATE_FILE, "2026-10-15\n");

        $release = $this->adapter->read($this->root, '4.12.2');

        $this->assertSame('2026-10-15', $release->releasedOn());
        $this->assertSame(['Zeta', 'Alpha'], array_map(static fn (Change $change): string => $change->message(), $release->changes()));
    }

    public function testReleaseMovesTheFragmentsIntoADatedVersionAndEmptiesUnreleased(): void
    {
        Carbon::setTestNow('2026-10-30 12:00:00');
        $this->writeFeature('unreleased/feature/x.xml', [new Change('added', 'New')]);

        $this->adapter->release($this->root, '4.13.0');

        $release = $this->adapter->read($this->root, '4.13.0');
        $this->assertSame('2026-10-30', $release->releasedOn());
        $this->assertCount(1, $release->changes());
        $this->assertSame([], $this->adapter->read($this->root, 'unreleased')->changes());
        $this->assertFileExists($this->root . '/unreleased/.gitkeep');
    }

    public function testReleaseRefusesAnExistingVersion(): void
    {
        File::ensureDirectoryExists($this->root . '/unreleased');
        File::ensureDirectoryExists($this->root . '/4.12.2');

        $this->expectException(VersionAlreadyExistsException::class);

        $this->adapter->release($this->root, '4.12.2');
    }

    /**
     * @param string $relativePath
     * @param list<Change> $changes
     * @return void
     */
    private function writeFeature(string $relativePath, array $changes): void
    {
        $this->app->make(FeatureAdapter::class)->write($this->root . '/' . $relativePath, new Feature($changes));
    }
}
