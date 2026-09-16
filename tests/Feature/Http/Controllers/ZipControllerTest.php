<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Services\DownloadCacheService;
use Illuminate\Support\Facades\URL;
use Tests\DatabaseTestCase;

class ZipControllerTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('queue.default', 'database');
    }

    public function testStartDispatchesZipJobAndInitializesCache(): void
    {
        $assignment = Assignment::factory()->create();
        $response = $this->postJson(URL::temporarySignedRoute('zips.start', now()->addHour(), [
            'batch' => $assignment->batch_id, 'channel' => $assignment->channel_id,
        ]), ['assignment_ids' => [$assignment->id]])->assertOk();

        $this->assertSame('queued', app(DownloadCacheService::class)->getStatus($response->json('jobId')));
        $this->assertDatabaseCount('jobs', 1);
        $this->assertCount(1, $response->json('downloads'));
    }

    public function testStartForChannelDispatchesZipJobAndInitializesCache(): void
    {
        $assignment = Assignment::factory()->create();
        $response = $this->postJson(URL::temporarySignedRoute('zips.channel.start', now()->addHour(), [
            'channel' => $assignment->channel_id,
        ]), ['assignment_ids' => [$assignment->id]])->assertOk();

        $this->assertSame('queued', app(DownloadCacheService::class)->getStatus($response->json('jobId')));
        $this->assertDatabaseCount('jobs', 1);
    }

    public function testStartReturnsErrorWhenAssignmentsAreMissing(): void
    {
        $this->postJson(URL::temporarySignedRoute('zips.start', now()->addHour(), [
            'batch' => Batch::factory()->create()->id, 'channel' => Channel::factory()->create()->id,
        ]), ['assignment_ids' => [123]])->assertUnprocessable();
        $this->assertDatabaseCount('jobs', 0);
    }

    public function testStartForChannelReturnsErrorWhenAssignmentsAreMissing(): void
    {
        $this->postJson(URL::temporarySignedRoute('zips.channel.start', now()->addHour(), [
            'channel' => Channel::factory()->create()->id,
        ]), ['assignment_ids' => [123]])->assertUnprocessable();
        $this->assertDatabaseCount('jobs', 0);
    }

    public function testProgressReturnsCachedValues(): void
    {
        $cache = app(DownloadCacheService::class);
        $cache->init('job-1');
        $cache->setStatus('job-1', 'ready');
        $cache->setProgress('job-1', 80);
        $cache->setName('job-1', 'clips.zip');
        $cache->setFileStatus('job-1', 'video.mp4', 'ready');
        $this->getJson(URL::temporarySignedRoute('zips.progress', now()->addHour(), ['id' => 'job-1']))
            ->assertOk()->assertJson([
                'status' => 'ready', 'progress' => 80, 'name' => 'clips.zip',
                'files' => ['video.mp4' => 'ready'],
            ]);
    }
}
