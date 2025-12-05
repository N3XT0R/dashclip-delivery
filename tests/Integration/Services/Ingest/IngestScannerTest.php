<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Ingest;

use App\DTO\FileInfoDto;
use App\Facades\DynamicStorage;
use App\Models\Clip;
use App\Models\Video;
use App\Services\Ingest\IngestScanner;
use Storage;
use Tests\DatabaseTestCase;
use Tests\Testing\Traits\CopyDiskTrait;

class IngestScannerTest extends DatabaseTestCase
{
    protected IngestScanner $ingestScanner;

    use CopyDiskTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ingestScanner = $this->app->make(IngestScanner::class);
    }

    public function testScanInboxReturnsNotEmptyIngestStats(): void
    {
        Storage::fake('local');
        $inboxPath = base_path('tests/Fixtures/Inbox/Videos');
        $inboxDisk = DynamicStorage::fromPath($inboxPath);
        $tmpDisk = Storage::fake('tmp');
        $tmpDisk->deleteDirectory('');
        $tmpDisk->makeDirectory('');

        $this->copyDisk($inboxDisk, $tmpDisk);
        $ingestStats = $this->ingestScanner->scanDisk($tmpDisk->path(''), 'local');
        $this->assertNotNull($ingestStats);
        $stats = $ingestStats->toArray();
        $this->assertSame(3, $ingestStats->total());
        $this->assertSame(['new' => 1, 'dups' => 2, 'err' => 0], $stats);
    }

    /**
     * @throws \Throwable
     */
    public function testProcessFileReturnsImportResultWithNew(): void
    {
        Storage::fake('local');
        $inboxPath = base_path('tests/Fixtures/Inbox/Videos/');
        $disk = DynamicStorage::fromPath($inboxPath);
        $fileInfoDto = new FileInfoDto(
            'standalone.mp4',
            'standalone.mp4',
            'mp4'
        );

        $importResult = $this->ingestScanner->processFile($disk, $fileInfoDto, 'local');
        $this->assertNotNull($importResult);
        self::assertSame('NEW', $importResult->name);
        // Assert: video created
        $this->assertDatabaseCount('videos', 1);
        $video = Video::first();
        $this->assertNotNull($video);
        $this->assertStringEndsWith('.mp4', $video->path);
        $this->assertSame('local', $video->disk);
        $this->assertNotEmpty($video->hash);

        // Assert: preview was generated
        $this->assertNotNull($video->preview_url);
        $this->assertStringEndsWith('.mp4', $video->preview_url);

        $this->assertDatabaseCount('clips', 1);
        $clip = Clip::first();
        $this->assertSame($video->id, $clip->video_id);
    }
}