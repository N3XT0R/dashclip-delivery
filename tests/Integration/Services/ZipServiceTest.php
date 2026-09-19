<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Video;
use App\Exceptions\Zip\ZipEmptyException;
use App\Services\CsvService;
use App\Services\DownloadCacheService;
use App\Services\Zip\ZipService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\DatabaseTestCase;
use ZipArchive;

class ZipServiceTest extends DatabaseTestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        // Use the real local filesystem so Storage::path() works with ZipArchive
        Config::set('filesystems.default', 'local');

        Storage::makeDirectory('videos');
        Storage::makeDirectory('zips');
        Storage::makeDirectory('zips/tmp');
    }

    protected function tearDown(): void
    {
        Storage::deleteDirectory('zips');
        Storage::deleteDirectory('videos');
        parent::tearDown();
    }

    /** Open a ZIP by storage-relative path and return the ZipArchive handle. */
    private function openZip(string $relPath): ZipArchive
    {
        $fsPath = Storage::path($relPath);
        $this->assertFileExists($fsPath, "ZIP not found at {$fsPath}");
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($fsPath) === true, "Unable to open ZIP: {$fsPath}");
        return $zip;
    }

    public function testBuildCreatesZipWithCsvAndLocalVideos(): void
    {
        // Arrange domain models
        $batch = Batch::factory()->create(['type' => 'assign', 'started_at' => now(), 'finished_at' => now()]);
        $channel = Channel::factory()->create(['name' => 'Main / Channel']);

        // Two real local source files
        Storage::put('videos/a.mp4', str_repeat('A', 1024));
        Storage::put('videos/illegal:name?.mov', str_repeat('B', 2048));

        $video1 = Video::factory()->create([
            'disk' => 'local',
            'path' => 'videos/a.mp4',
            'hash' => 'h1',
            'bytes' => 1024,
            'ext' => 'mp4',
            'original_name' => null, // uses basename(path)
        ]);
        $video2 = Video::factory()->create([
            'disk' => 'local',
            'path' => 'videos/illegal:name?.mov',
            'hash' => 'h2',
            'bytes' => 2048,
            'ext' => 'mov',
            'original_name' => 'bad:name?.mov', // will be sanitized
        ]);

        $a1 = Assignment::factory()->for($batch, 'batch')->for($channel, 'channel')->for($video1, 'video')->create();
        $a2 = Assignment::factory()->for($batch, 'batch')->for($channel, 'channel')->for($video2, 'video')->create();

        // Test double: swallow all cache calls (no expectations)
        $cacheDouble = Mockery::mock(DownloadCacheService::class)->shouldIgnoreMissing();
        $svc = new ZipService($cacheDouble, $this->app->make(CsvService::class));

        // Act
        $zipRel = $svc->build($batch, $channel, collect([$a1, $a2]), '203.0.113.10', 'UA/1.0');

        // Assert: path and ZIP contents
        $expectedRel = "zips/{$batch->id}_{$channel->id}.zip";
        $this->assertSame($expectedRel, $zipRel);

        $zip = $this->openZip($zipRel);

        // CSV present with header
        $this->assertNotFalse($zip->locateName('info.csv'));
        $csv = (string)$zip->getFromName('info.csv');
        $this->assertStringContainsString('filename;hash;size_mb;start;end;note;bundle;role;submitted_by',
            str_replace(',', ';', $csv));

        // Files present; sanitized names
        $this->assertNotFalse($zip->locateName('a.mp4'));          // from basename(path)
        $this->assertNotFalse($zip->locateName('bad_name_.mov'));  // original_name sanitized

        $zip->close();
    }

    public function testDropboxTransferReportsProgressWhileALargeVideoIsCopied(): void
    {
        $root = storage_path('app/dropbox-progress-sim');
        if (!is_dir($root)) {
            mkdir($root, 0777, true);
        }
        Config::set('filesystems.disks.dropbox', ['driver' => 'local', 'root' => $root]);
        Storage::forgetDisk('dropbox');
        $bytes = (int) (ZipService::TRANSFER_CHUNK_BYTES * 2.5);
        Storage::disk('dropbox')->put('remote/large.mp4', str_repeat('L', $bytes));

        $channel = Channel::factory()->create();
        $video = Video::factory()->create([
            'disk' => 'dropbox', 'path' => 'remote/large.mp4', 'bytes' => $bytes, 'original_name' => 'large.mp4',
        ]);
        $assignment = Assignment::factory()->for($channel, 'channel')->for($video, 'video')->create();

        $progress = [];
        $cache = Mockery::mock(DownloadCacheService::class)->shouldIgnoreMissing();
        $cache->shouldReceive('setProgress')->andReturnUsing(function (string $jobId, int $value) use (&$progress): void {
            $progress[] = $value;
        });

        $zipRel = (new ZipService($cache, $this->app->make(CsvService::class)))
            ->build(null, $channel, collect([$assignment]), '198.51.100.7', 'UA/2.0', 'progress-job');

        $intermediate = array_filter($progress, static fn (int $value): bool => $value > 0 && $value < 100);
        $this->assertGreaterThanOrEqual(2, count(array_unique($intermediate)), 'Progress: '.implode(',', $progress));
        $sorted = $progress;
        sort($sorted);
        $this->assertSame($sorted, $progress, 'Progress never moves backwards.');
        $this->assertSame(100, end($progress));

        $zip = $this->openZip($zipRel);
        $this->assertSame($bytes, $zip->statName('large.mp4')['size']);
        $zip->close();
        Storage::disk('dropbox')->deleteDirectory('remote');
    }

    public function testBuildDownloadsFromDropboxViaReadStreamAndPacksIntoZip(): void
    {
        // Simulate a "dropbox" disk with a local driver so readStream() works without network
        $root = storage_path('app/dropbox-sim');
        if (!is_dir($root)) {
            mkdir($root, 0777, true);
        }
        Config::set('filesystems.disks.dropbox', [
            'driver' => 'local',
            'root' => $root,
        ]);

        // Source on simulated dropbox
        Storage::disk('dropbox')->put('remote/videoC.mp4', str_repeat('C', 4096));

        $batch = Batch::factory()->create(['type' => 'assign', 'started_at' => now(), 'finished_at' => now()]);
        $channel = Channel::factory()->create(['name' => 'Dropbox Channel']);
        $video = Video::factory()->create([
            'disk' => 'dropbox',
            'path' => 'remote/videoC.mp4',
            'hash' => 'hC',
            'bytes' => 4096,
            'ext' => 'mp4',
            'original_name' => 'clipC.mp4',
        ]);
        $a = Assignment::factory()->for($batch, 'batch')->for($channel, 'channel')->for($video, 'video')->create();

        $cacheDouble = Mockery::mock(DownloadCacheService::class)->shouldIgnoreMissing();

        $svc = new ZipService($cacheDouble, $this->app->make(CsvService::class));

        // Act
        $zipRel = $svc->build($batch, $channel, collect([$a]), '198.51.100.7', 'UA/2.0');

        // Assert: ZIP contains csv and the file with original name
        $zip = $this->openZip($zipRel);
        $this->assertNotFalse($zip->locateName('info.csv'));
        $this->assertNotFalse($zip->locateName('clipC.mp4'));
        $zip->close();
    }

    public function testBuildArchiveWritesPackedOffersAndTheirInfoCsvToTheGivenPath(): void
    {
        $channel = Channel::factory()->create();
        Storage::put('videos/good.mp4', 'good-video');
        $good = Assignment::factory()->for($channel, 'channel')->for(Video::factory()->create([
            'disk' => 'local', 'path' => 'videos/good.mp4', 'bytes' => 10, 'original_name' => 'good.mp4',
        ]), 'video')->create();
        $missing = Assignment::factory()->for($channel, 'channel')->for(Video::factory()->create([
            'disk' => 'local', 'path' => 'videos/missing.mp4', 'bytes' => 10, 'original_name' => 'missing.mp4',
        ]), 'video')->create();
        $target = Storage::path('zips/archive-test/offers.zip');

        $cache = Mockery::mock(DownloadCacheService::class)->shouldIgnoreMissing();
        $csv = $this->app->make(CsvService::class);
        $packed = (new ZipService($cache, $csv))->buildArchive($target, $channel, collect([$good, $missing]), 'export-1');

        $this->assertSame([$good->id], $packed->modelKeys());
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($target) === true);
        $this->assertSame(2, $zip->numFiles);
        $this->assertSame('good-video', $zip->getFromName('good.mp4'));
        $this->assertSame($csv->buildInfoCsv(collect([$good])), $zip->getFromName('info.csv'));
        $zip->close();
        Storage::deleteDirectory('zips/archive-test');
    }

    public function testBuildArchiveLeavesNoFileWhenNothingCanBePacked(): void
    {
        $channel = Channel::factory()->create();
        $missing = Assignment::factory()->for($channel, 'channel')->for(Video::factory()->create([
            'disk' => 'local', 'path' => 'videos/missing.mp4', 'bytes' => 10,
        ]), 'video')->create();
        $target = Storage::path('zips/archive-test/empty.zip');

        $cache = Mockery::mock(DownloadCacheService::class)->shouldIgnoreMissing();
        try {
            (new ZipService($cache, $this->app->make(CsvService::class)))
                ->buildArchive($target, $channel, collect([$missing]), 'export-2');
            $this->fail('An archive without videos must not be built.');
        } catch (ZipEmptyException) {
            $this->assertFileDoesNotExist($target);
        }
        Storage::deleteDirectory('zips/archive-test');
    }
}
