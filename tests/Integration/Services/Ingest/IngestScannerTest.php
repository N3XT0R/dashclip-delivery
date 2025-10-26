<?php

declare(strict_types=1);

namespace Services\Ingest;

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
        \Storage::fake('local');
        $inboxPath = base_path('tests/Fixtures/Inbox/Videos');
        $ingestStats = $this->ingestScanner->scanDisk($inboxPath, 'local');
        $this->assertNotNull($ingestStats);
        $stats = $ingestStats->toArray();
        $this->assertSame(3, $ingestStats->total());
        $this->assertSame(['new' => 3, 'dups' => 0, 'err' => 0], $stats);
    }
}