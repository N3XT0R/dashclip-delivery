<?php

declare(strict_types=1);

namespace Services\Ingest;

use App\DTO\FileInfoDto;
use App\Facades\DynamicStorage;
use App\Models\Video;
use App\Services\Ingest\IngestScanner;
use Tests\DatabaseTestCase;

class IngestScannerTest extends DatabaseTestCase
{
    protected IngestScanner $ingestScanner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ingestScanner = $this->app->make(IngestScanner::class);
    }

    public function testScanInboxReturnsNozEmptyIngestStats(): void
    {
        $this->markTestSkipped('something is buggy with import here');
        \Storage::fake('local');
        $inboxPath = base_path('tests/Fixtures/Inbox/Videos');
        $ingestStats = $this->ingestScanner->scanDisk($inboxPath, 'local');
        $this->assertNotNull($ingestStats);
        $stats = $ingestStats->toArray();
        $this->assertSame(3, $ingestStats->total());
        $this->assertSame(['new' => 3, 'dups' => 0, 'err' => 0], $stats);
    }

    /**
     * @throws \Throwable
     */
    public function testProcessFileReturnsImportResultWithNew(): void
    {
        \Storage::fake('local');
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
    }
}