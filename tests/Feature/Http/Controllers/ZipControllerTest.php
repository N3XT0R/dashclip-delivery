<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enum\DownloadStatusEnum;
use App\Jobs\BuildZipJob;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Services\DownloadCacheService;
use Illuminate\Support\Facades\Queue;
use Mockery;
use ReflectionClass;
use Tests\DatabaseTestCase;

class ZipControllerTest extends DatabaseTestCase
{
    public function testStartDispatchesZipJobAndInitializesCache(): void
    {
        Queue::fake();

        $batch = Batch::factory()->create();
        $channel = Channel::factory()->create();
        $assignment = Assignment::factory()
            ->for($channel)
            ->withBatch($batch)
            ->create();

        $downloadCache = Mockery::mock(DownloadCacheService::class);
        $downloadCache->shouldReceive('init')
            ->once()
            ->with($batch->id . '_' . $channel->id);
        $this->app->instance(DownloadCacheService::class, $downloadCache);

        $response = $this->postJson("/zips/{$batch->id}/{$channel->id}", [
            'assignment_ids' => [$assignment->id, 'invalid'],
        ]);

        $response->assertOk();
        $response->assertJson([
            'jobId' => $batch->id . '_' . $channel->id,
            'status' => DownloadStatusEnum::QUEUED->value,
        ]);

        Queue::assertPushed(BuildZipJob::class, static function (BuildZipJob $job) use ($assignment) {
            $ref = new ReflectionClass($job);
            $ids = $ref->getProperty('assignmentIds');
            $ids->setAccessible(true);

            return $ids->getValue($job) === [$assignment->id];
        });
    }

    public function testStartForChannelDispatchesZipJobAndInitializesCache(): void
    {
        Queue::fake();

        $batch = Batch::factory()->create();
        $channel = Channel::factory()->create();
        $assignment = Assignment::factory()
            ->for($channel)
            ->withBatch($batch)
            ->create();

        $jobId = $channel->id;

        $downloadCache = Mockery::mock(DownloadCacheService::class);
        $downloadCache->shouldReceive('init')
            ->once()
            ->with($batch->id . '_' . $channel->id);
        $this->app->instance(DownloadCacheService::class, $downloadCache);

        $response = $this->postJson("/zips/channel/{$channel->id}", [
            'assignment_ids' => [$assignment->id, 'invalid'],
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => DownloadStatusEnum::QUEUED->value,
        ]);

        Queue::assertPushed(BuildZipJob::class, static function (BuildZipJob $job) use ($assignment) {
            $ref = new ReflectionClass($job);
            $ids = $ref->getProperty('assignmentIds');
            $ids->setAccessible(true);

            return $ids->getValue($job) === [$assignment->id];
        });
    }

    public function testStartReturnsErrorWhenAssignmentsAreMissing(): void
    {
        Queue::fake();

        $batch = Batch::factory()->create();
        $channel = Channel::factory()->create();

        $downloadCache = Mockery::mock(DownloadCacheService::class);
        $downloadCache->shouldReceive('init')->never();
        $this->app->instance(DownloadCacheService::class, $downloadCache);

        $response = $this->postJson("/zips/{$batch->id}/{$channel->id}", [
            'assignment_ids' => [123],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'Die Auswahl ist nicht mehr verfügbar.',
        ]);

        Queue::assertNothingPushed();
    }

    public function testProgressReturnsCachedValues(): void
    {
        $downloadCache = Mockery::mock(DownloadCacheService::class);
        $downloadCache->shouldReceive('getStatus')
            ->once()
            ->with('job-1')
            ->andReturn(DownloadStatusEnum::READY->value);
        $downloadCache->shouldReceive('getProgress')
            ->once()
            ->with('job-1')
            ->andReturn(80);
        $downloadCache->shouldReceive('getName')
            ->once()
            ->with('job-1')
            ->andReturn('clips.zip');
        $this->app->instance(DownloadCacheService::class, $downloadCache);

        $response = $this->getJson('/zips/job-1/progress');

        $response->assertOk();
        $response->assertJson([
            'status' => DownloadStatusEnum::READY->value,
            'progress' => 80,
            'name' => 'clips.zip',
        ]);
    }
}
