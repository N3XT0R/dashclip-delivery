<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Enum\DownloadStatusEnum;
use App\Events\ZipProgressUpdated;
use App\Services\DownloadCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Integration tests for DownloadCacheService.
 *
 * - Uses the in-memory array cache to avoid external dependencies.
 * - Verifies cache mutations (status/progress/files/file/assignments/name).
 * - Verifies that status updates do not depend on broadcasting.
 */
class DownloadCacheServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure an isolated, in-memory cache for tests
        config()->set('cache.default', 'array');
        Cache::flush();

        // Fake events to assert dispatches (no actual broadcasting)
        Event::fake();
    }

    public function testInitSeedsCacheWithoutBroadcasting(): void
    {
        $svc = new DownloadCacheService();
        $jobId = 'job-1';

        $svc->init($jobId);

        // Cache state
        $this->assertSame(DownloadStatusEnum::QUEUED->value, $svc->getStatus($jobId));
        $this->assertSame(0, $svc->getProgress($jobId));
        $this->assertSame([], $svc->getFiles($jobId));
        $this->assertNull($svc->getFile($jobId));
        $this->assertSame([], $svc->getAssignments($jobId));
        $this->assertNull($svc->getName($jobId));

        Event::assertNotDispatched(ZipProgressUpdated::class);
    }

    public function testSettersUpdateCacheWithoutBroadcasting(): void
    {
        $svc = new DownloadCacheService();
        $jobId = 'job-2';

        // Prime with init, but don't count its events in this test section
        $svc->init($jobId);
        Event::fake(); // reset event recorder

        // Mutations
        $svc->setName($jobId, 'bundle-aug.zip');
        $svc->setProgress($jobId, 10);
        $svc->setFileStatus($jobId, 'a.mp4', 'queued');
        $svc->setStatus($jobId, 'processing'); // free-form string accepted by service

        // Cache expectations
        $this->assertSame('processing', $svc->getStatus($jobId));
        $this->assertSame(10, $svc->getProgress($jobId));
        $this->assertSame(['a.mp4' => 'queued'], $svc->getFiles($jobId));
        $this->assertSame('bundle-aug.zip', $svc->getName($jobId));

        Event::assertNotDispatched(ZipProgressUpdated::class);
    }

    public function testSetFileAndAssignmentsRoundtrip(): void
    {
        $svc = new DownloadCacheService();
        $jobId = 'job-3';

        $svc->setFile($jobId, '/tmp/bundle.zip');
        $svc->setAssignments($jobId, [11, 22, 33]);

        $this->assertSame('/tmp/bundle.zip', $svc->getFile($jobId));
        $this->assertSame([11, 22, 33], $svc->getAssignments($jobId));

        // These two setters do NOT broadcast in your service (only setName/setStatus/setProgress/setFileStatus do).
        Event::assertDispatchedTimes(ZipProgressUpdated::class, 0);
    }

    public function testDefaultsWhenNoDataExists(): void
    {
        $svc = new DownloadCacheService();
        $jobId = 'unknown-job';

        $this->assertSame(DownloadStatusEnum::UNKNOWN->value, $svc->getStatus($jobId));
        $this->assertSame(0, $svc->getProgress($jobId));
        $this->assertSame([], $svc->getFiles($jobId));
        $this->assertNull($svc->getFile($jobId));
        $this->assertSame([], $svc->getAssignments($jobId));
        $this->assertSame('Untitled', $svc->getName($jobId, 'Untitled'));

        // No broadcasts, we only read
        Event::assertDispatchedTimes(ZipProgressUpdated::class, 0);
    }
}
