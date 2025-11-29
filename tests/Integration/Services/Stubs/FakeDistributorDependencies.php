<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Stubs;

use App\DTO\UploaderPoolInfo;
use App\Models\Batch;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Repository\AssignmentRepository;
use App\Repository\ChannelVideoBlockRepository;
use App\Repository\ClipRepository;
use App\Repository\VideoRepository;
use App\Services\AssignmentService;
use App\Services\BatchService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;

final class FakeDistributorDependencies
{
    public AssignmentRepository|MockInterface $assignmentRepository;

    public AssignmentService $assignmentService;

    public ChannelVideoBlockRepository|MockInterface $channelVideoBlockRepository;

    public BatchService|MockInterface $batchService;

    public VideoRepository|MockInterface $videoRepository;

    public Team $team;

    public User $user;

    public object $batch;

    public static function make(Application $app): array
    {
        $stubs = new self();

        $stubs->assignmentRepository = Mockery::mock(AssignmentRepository::class);
        $stubs->assignmentService = new AssignmentService($stubs->assignmentRepository);
        $stubs->channelVideoBlockRepository = Mockery::mock(ChannelVideoBlockRepository::class);
        $stubs->batchService = Mockery::mock(BatchService::class);
        $stubs->videoRepository = Mockery::mock(VideoRepository::class);

        $stubs->team = self::createTeam();
        $stubs->user = self::createUser();
        $stubs->batch = Batch::factory()->make();

        $app->instance(VideoRepository::class, $stubs->videoRepository);
        $app->instance(ClipRepository::class, self::fakeClipRepository());

        $distributor = new InstrumentedAssignmentDistributor(
            $stubs->assignmentRepository,
            $stubs->assignmentService,
            $stubs->channelVideoBlockRepository,
            $stubs->batchService
        );

        return [$distributor, $stubs];
    }

    public static function createTeam(): Team
    {
        return Team::factory()->create();
    }

    public static function createUser(): User
    {
        return User::factory()->create();
    }

    public function uploaderPoolsForTeam(Video $video): array
    {
        return [new UploaderPoolInfo('team', $this->team->slug, collect([$video]))];
    }

    public function uploaderPoolsForUser(Video $video, int $userId): array
    {
        return [new UploaderPoolInfo('user', $userId, collect([$video]))];
    }

    public function uploaderPoolsForFallback(Video $video): array
    {
        return [new UploaderPoolInfo('user', 0, collect([$video]))];
    }

    public function prepareBatchWithPool(Collection $videos): void
    {
        $this->batchService->shouldReceive('startBatch')
            ->andReturn($this->batch);

        $this->batchService->shouldReceive('collectVideosForAssign')
            ->andReturn($videos);

        $this->batchService->shouldReceive('finishAssignBatch')->once();

        $this->channelVideoBlockRepository
            ->shouldReceive('preloadActiveBlocks')
            ->andReturn([]);

        $this->assignmentRepository
            ->shouldReceive('preloadAssignedChannels')
            ->andReturn([]);
    }

    private static function fakeClipRepository(): MockInterface
    {
        $clipRepository = Mockery::mock(ClipRepository::class);

        $clipRepository->shouldReceive('getBundleKeysForVideos')->andReturn(collect());
        $clipRepository->shouldReceive('getVideoIdsForBundleKeys')->andReturn(collect());
        $clipRepository->shouldReceive('getBundleVideoMap')->andReturn(collect());

        return $clipRepository;
    }
}