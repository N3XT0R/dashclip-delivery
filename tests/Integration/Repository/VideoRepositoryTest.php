<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Models\Video;
use App\Repository\VideoRepository;
use Tests\DatabaseTestCase;

class VideoRepositoryTest extends DatabaseTestCase
{
    protected VideoRepository $videoRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->videoRepository = $this->app->make(VideoRepository::class);
    }

    public function testReturnsVideosForExistingIds(): void
    {
        $v1 = Video::factory()->create();
        $v2 = Video::factory()->create();

        $result = $this->videoRepository->getVideosByIds([$v1->id, $v2->id]);

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $v1->id));
        $this->assertTrue($result->contains('id', $v2->id));
    }

    public function testReturnsEmptyCollectionWhenNoIdsMatch(): void
    {
        $result = $this->videoRepository->getVideosByIds([999, 1000]);

        $this->assertCount(0, $result);
    }

    public function testIgnoresInvalidIdsAndReturnsOnlyExistingVideos(): void
    {
        $v1 = Video::factory()->create();

        $result = $this->videoRepository->getVideosByIds([$v1->id, 999999]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $v1->id));
    }

    public function testHandlesDifferentIterableTypes(): void
    {
        $v1 = Video::factory()->create();
        $v2 = Video::factory()->create();

        $iterable = collect([$v1->id, $v2->id]);

        $result = $this->videoRepository->getVideosByIds($iterable);

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $v1->id));
        $this->assertTrue($result->contains('id', $v2->id));
    }
}