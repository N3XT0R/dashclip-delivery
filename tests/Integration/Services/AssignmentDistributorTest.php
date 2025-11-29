<?php

namespace Tests\Integration\Services;

use App\Models\{Assignment, Channel, Clip, Video};
use App\Services\AssignmentDistributor;
use RuntimeException;
use Tests\DatabaseTestCase;
use Tests\Integration\Services\Stubs\FakeDistributorDependencies;

class AssignmentDistributorTest extends DatabaseTestCase
{

    protected AssignmentDistributor $assignmentDistributor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assignmentDistributor = $this->app->make(AssignmentDistributor::class);
    }

    public function testBundleVideosAreAssignedTogether(): void
    {
        $v1 = Video::create(['hash' => 'h1', 'path' => 'p1']);
        $v2 = Video::create(['hash' => 'h2', 'path' => 'p2']);
        Clip::create(['video_id' => $v1->id, 'bundle_key' => 'B']);
        Clip::create(['video_id' => $v2->id, 'bundle_key' => 'B']);

        $result = $this->assignmentDistributor->distribute();

        $this->assertSame(2, $result['assigned']);
        $assignments = Assignment::query()->whereIn('video_id', [$v1->getKey(), $v2->getKey()])->get();
        $this->assertCount(2, $assignments);
        $this->assertSame(1, $assignments->pluck('channel_id')->unique()->count());
    }

    public function testDistributorHandlesInitialRunWithoutPreviousBatch(): void
    {
        $video = Video::create(['hash' => 'h1', 'path' => 'p1']);

        $result = $this->assignmentDistributor->distribute();

        $this->assertSame(['assigned' => 1, 'skipped' => 0], $result);
        $this->assertDatabaseHas('assignments', ['video_id' => $video->getKey()]);
    }

    public function testPrepareChannelsOrAbortThrowsWhenNoChannelsExist(): void
    {
        // Arrange
        Channel::query()->delete();
        // One video => pool is non-empty → next step must fail at channel check
        Video::factory()->create();

        $distributor = $this->assignmentDistributor;

        // Act + Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Keine Kanäle konfiguriert.');

        $distributor->distribute();
    }

    public function testDistributorUsesTeamSlugForPartitioning(): void
    {
        $teamVideo = Video::factory()->create(['team_id' => FakeDistributorDependencies::createTeam()->getKey()]);

        [$distributor, $stubs] = FakeDistributorDependencies::make($this->app);

        $stubs->videoRepository
            ->shouldReceive('partitionByTeamOrUploader')
            ->once()
            ->andReturn($stubs->uploaderPoolsForTeam($teamVideo));

        $stubs->prepareBatchWithPool(collect([$teamVideo]));

        $distributor->distribute();

        $this->assertCount(1, $distributor->prepareChannelCalls);
        $prepareCall = $distributor->prepareChannelCalls->first();
        $this->assertSame('team', $prepareCall['uploaderType']);
        $this->assertSame($stubs->team->slug, $prepareCall['uploaderId']);

        $this->assertCount(1, $distributor->assignGroupRuns);
        $run = $distributor->assignGroupRuns->first();
        $this->assertSame('team', $run->uploaderType);
        $this->assertSame($stubs->team->slug, $run->uploaderId);

        $stubs->batchService->shouldHaveReceived('finishAssignBatch')->with($stubs->batch, 0, 0);
    }

    public function testDistributorFallsBackToUploaderWhenUserIdIsPresent(): void
    {
        $userVideo = Video::factory()->create();
        Clip::factory()->for($userVideo)->create(['user_id' => FakeDistributorDependencies::createUser()->getKey()]);

        [$distributor, $stubs] = FakeDistributorDependencies::make($this->app);

        $stubs->videoRepository
            ->shouldReceive('partitionByTeamOrUploader')
            ->once()
            ->andReturn($stubs->uploaderPoolsForUser($userVideo, $stubs->user->getKey()));

        $stubs->prepareBatchWithPool(collect([$userVideo]));

        $distributor->distribute();

        $this->assertCount(1, $distributor->prepareChannelCalls);
        $prepareCall = $distributor->prepareChannelCalls->first();
        $this->assertSame('user', $prepareCall['uploaderType']);
        $this->assertSame($stubs->user->getKey(), $prepareCall['uploaderId']);

        $run = $distributor->assignGroupRuns->first();
        $this->assertSame('user', $run->uploaderType);
        $this->assertSame($stubs->user->getKey(), $run->uploaderId);

        $stubs->batchService->shouldHaveReceived('finishAssignBatch')->with($stubs->batch, 0, 0);
    }

    public function testDistributorUsesFallbackPoolWhenTeamAndUserAreMissing(): void
    {
        $orphanVideo = Video::factory()->create();
        Clip::factory()->for($orphanVideo)->create(['user_id' => null]);

        [$distributor, $stubs] = FakeDistributorDependencies::make($this->app);

        $stubs->videoRepository
            ->shouldReceive('partitionByTeamOrUploader')
            ->once()
            ->andReturn($stubs->uploaderPoolsForFallback($orphanVideo));

        $stubs->prepareBatchWithPool(collect([$orphanVideo]));

        $distributor->distribute();

        $this->assertCount(1, $distributor->prepareChannelCalls);
        $prepareCall = $distributor->prepareChannelCalls->first();
        $this->assertSame('user', $prepareCall['uploaderType']);
        $this->assertSame(0, $prepareCall['uploaderId']);

        $run = $distributor->assignGroupRuns->first();
        $this->assertSame('user', $run->uploaderType);
        $this->assertSame(0, $run->uploaderId);

        $stubs->batchService->shouldHaveReceived('finishAssignBatch')->with($stubs->batch, 0, 0);
    }
}
