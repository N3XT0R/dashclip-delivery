<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Exceptions\InvalidTimeRangeException;
use App\Exceptions\PreviewGenerationException;
use App\Services\PreviewService;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

class PreviewServiceTest extends DatabaseTestCase
{

    protected PreviewService $previewService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previewService = $this->app->make(PreviewService::class);
    }

    public function testGeneratePreviewByDiskCreatesPreviewSuccessfully(): void
    {
        // prepare input video (real fixture)
        $fixtureDir = base_path('tests/Fixtures/Inbox/Videos');
        $fixtureVideo = $fixtureDir.'/standalone.mp4';
        $this->assertFileExists($fixtureVideo, 'Fixture video missing: '.$fixtureVideo);

        // use the real local disk from the fixture directory
        $disk = Storage::build([
            'driver' => 'local',
            'root' => $fixtureDir,
        ]);

        // ensure a fresh fake target disk for previews
        Storage::fake('public');
        config(['preview.default_disk' => 'public']);

        $relativePath = 'standalone.mp4';
        $startSec = 1;
        $endSec = 3;

        // calculate expected SHA-256 hash (like DynamicStorage::getHashForFilePath)
        $expectedHash = hash_file('sha256', $fixtureVideo);
        $sub = substr($expectedHash, 0, 2).'/'.substr($expectedHash, 2, 2);
        $expectedPath = sprintf('previews/%s/%s.mp4', $sub, $expectedHash);

        // act
        $url = $this->previewService->generatePreviewByDisk(
            $disk,
            $relativePath,
            id: null,
            startSec: $startSec,
            endSec: $endSec
        );

        // assert
        $this->assertIsString($url);
        $this->assertStringContainsString($expectedPath, $url, 'Preview URL does not match expected path.');

        // preview file should exist on fake disk
        $previewDisk = Storage::disk('public');
        $this->assertTrue(
            $previewDisk->exists($expectedPath),
            'Expected preview file not found on target disk'
        );

        $size = $previewDisk->size($expectedPath);
        $this->assertGreaterThan(0, $size, 'Generated preview file has zero bytes');
    }

    public function testGeneratePreviewByDiskReturnsCachedUrlWhenPreviewExists(): void
    {
        // setup fixture disk
        $fixtureDir = base_path('tests/Fixtures/Inbox/Videos');
        $fixtureVideo = $fixtureDir.'/standalone.mp4';
        $this->assertFileExists($fixtureVideo, 'Fixture video missing');

        $disk = Storage::build([
            'driver' => 'local',
            'root' => $fixtureDir,
        ]);

        // target disk fake
        Storage::fake('public');
        config(['preview.default_disk' => 'public']);

        $relativePath = 'standalone.mp4';

        // pre-compute expected path like PathBuilder::forPreviewByHash()
        $hash = hash_file('sha256', $fixtureVideo);
        $sub = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        $previewPath = sprintf('previews/%s/%s.mp4', $sub, $hash);

        // create a dummy cached preview file
        $cachedContent = 'EXISTING_PREVIEW';
        Storage::disk('public')->put($previewPath, $cachedContent);

        // act
        $url = $this->previewService->generatePreviewByDisk(
            $disk,
            $relativePath,
            id: null,
            startSec: 1,
            endSec: 3
        );

        // assert
        $this->assertIsString($url);
        $this->assertStringContainsString($previewPath, $url);
        $this->assertSame($cachedContent, Storage::disk('public')->get($previewPath));

        // ensure ffmpeg was NOT executed by checking no new files created
        $allFiles = Storage::disk('public')->allFiles();
        $this->assertCount(1, $allFiles, 'Expected only cached preview to exist');
    }

    public function testGeneratePreviewByDiskThrowsPreviewGenerationExceptionOnInvalidInput(): void
    {
        $fixtureDir = base_path('tests/Fixtures/Inbox/Videos');
        $fixtureFile = $fixtureDir.'/notizen.csv';
        $this->assertFileExists($fixtureFile, 'Fixture CSV missing');

        $disk = Storage::build([
            'driver' => 'local',
            'root' => $fixtureDir,
        ]);

        // fake target disk
        Storage::fake('public');
        config(['preview.default_disk' => 'public']);

        $relativePath = 'notizen.csv';

        $this->expectException(PreviewGenerationException::class);

        // act — ffmpeg will fail since it's not a video file
        $this->previewService->generatePreviewByDisk(
            $disk,
            $relativePath,
            id: null,
            startSec: 0,
            endSec: 2
        );
    }

    public function testGeneratePreviewByDiskThrowsInvalidTimeRangeExceptionWhenStartAfterEnd(): void
    {
        $fixtureDir = base_path('tests/Fixtures/Inbox/Videos');
        $fixtureVideo = $fixtureDir.'/standalone.mp4';
        $this->assertFileExists($fixtureVideo, 'Fixture video missing: '.$fixtureVideo);

        // real local source disk
        $disk = Storage::build([
            'driver' => 'local',
            'root' => $fixtureDir,
        ]);

        // fake target disk
        Storage::fake('public');
        config(['preview.default_disk' => 'public']);

        $relativePath = 'standalone.mp4';
        $startSec = 5;
        $endSec = 1;

        $this->expectException(InvalidTimeRangeException::class);

        // act — should fail immediately before ffmpeg is called
        $this->previewService->generatePreviewByDisk(
            $disk,
            $relativePath,
            id: null,
            startSec: $startSec,
            endSec: $endSec
        );
    }

}
