<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Download;
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
}
