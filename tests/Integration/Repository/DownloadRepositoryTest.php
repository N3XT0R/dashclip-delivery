<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Download;
use App\Models\User;
use App\Models\Video;
use App\Repository\DownloadRepository;
use Tests\DatabaseTestCase;

final class DownloadRepositoryTest extends DatabaseTestCase
{
    private DownloadRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(DownloadRepository::class);
    }

    public function testReturnsVideoIdFromExpiredPickedUpAssignment(): void
    {
        $video = Video::factory()->create();
        $assignment = Assignment::factory()->for($video, 'video')->create([
            'status' => StatusEnum::PICKEDUP->value,
            'expires_at' => now()->subDay(),
        ]);
        Download::factory()->forAssignment($assignment)->create();

        $ids = $this->repository->fetchDownloadedVideoIds(now());

        $this->assertContains($video->getKey(), $ids->all());
    }

    public function testExcludesVideoFromNonPickedUpAssignment(): void
    {
        $video = Video::factory()->create();
        $assignment = Assignment::factory()->for($video, 'video')->create([
            'status' => StatusEnum::QUEUED->value,
            'expires_at' => now()->subDay(),
        ]);
        Download::factory()->forAssignment($assignment)->create();

        $ids = $this->repository->fetchDownloadedVideoIds(now());

        $this->assertNotContains($video->getKey(), $ids->all());
    }

    public function testExcludesVideoFromNotYetExpiredAssignment(): void
    {
        $video = Video::factory()->create();
        $assignment = Assignment::factory()->for($video, 'video')->create([
            'status' => StatusEnum::PICKEDUP->value,
            'expires_at' => now()->addDay(),
        ]);
        Download::factory()->forAssignment($assignment)->create();

        $ids = $this->repository->fetchDownloadedVideoIds(now());

        $this->assertNotContains($video->getKey(), $ids->all());
    }

    public function testReturnsVideoIdOnlyOnceWhenMultipleDownloadsExist(): void
    {
        $video = Video::factory()->create();
        $assignment = Assignment::factory()->for($video, 'video')->create([
            'status' => StatusEnum::PICKEDUP->value,
            'expires_at' => now()->subDay(),
        ]);
        Download::factory()->forAssignment($assignment)->count(3)->create();

        $ids = $this->repository->fetchDownloadedVideoIds(now());

        $this->assertCount(1, $ids->filter(fn($id) => $id === $video->getKey()));
    }

    public function testForUserReturnsOnlyDownloadsOfVideosWithUsersClips(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->withClips(1, $user)->create();
        $assignment = Assignment::factory()->for($video, 'video')->create();
        $download = Download::factory()->forAssignment($assignment)->create();

        $ids = $this->repository->forUser($user)->pluck('id');

        $this->assertContains($download->getKey(), $ids->all());
        $this->assertCount(1, $ids);
    }

    public function testForUserExcludesDownloadsOfOtherUsersVideos(): void
    {
        $user = User::factory()->create();
        $otherVideo = Video::factory()->withClips(1)->create();
        $otherAssignment = Assignment::factory()->for($otherVideo, 'video')->create();
        Download::factory()->forAssignment($otherAssignment)->create();

        $ids = $this->repository->forUser($user)->pluck('id');

        $this->assertCount(0, $ids);
    }
}
