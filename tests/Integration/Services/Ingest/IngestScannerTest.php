<?php

declare(strict_types=1);

namespace Services\Ingest;

use App\DTO\FileInfoDto;
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

    public function testScanInboxReturnsFileInfoDtos(): void
    {
        \Storage::fake('local');
        $inboxPath = base_path('tests/Fixtures/Inbox');
        $fileInfoDtos = $this->ingestScanner->scanDisk($inboxPath, 'local');
        $this->assertCount(1, $fileInfoDtos);
        foreach ($fileInfoDtos as $fileInfoDto) {
            $this->assertInstanceOf(FileInfoDto::class, $fileInfoDto);
        }
    }
}