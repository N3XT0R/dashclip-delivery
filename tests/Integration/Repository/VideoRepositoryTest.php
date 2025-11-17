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

    public function testGetVideosByIdsReturnsVideosForExistingIds(): void
    {
        $v1 = Video::factory()->create();
        $v2 = Video::factory()->create();

        $result = $this->videoRepository->getVideosByIds([$v1->id, $v2->id]);

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $v1->id));
        $this->assertTrue($result->contains('id', $v2->id));
    }

    public function testGetVideosByIdsReturnsEmptyCollectionWhenNoIdsMatch(): void
    {
        $result = $this->videoRepository->getVideosByIds([999, 1000]);

        $this->assertCount(0, $result);
    }

    public function testGetVideosByIdsIgnoresInvalidIdsAndReturnsOnlyExistingVideos(): void
    {
        $v1 = Video::factory()->create();

        $result = $this->videoRepository->getVideosByIds([$v1->id, 999999]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $v1->id));
    }

    public function testGetVideosByIdsHandlesDifferentIterableTypes(): void
    {
        $v1 = Video::factory()->create();
        $v2 = Video::factory()->create();

        $iterable = collect([$v1->id, $v2->id]);

        $result = $this->videoRepository->getVideosByIds($iterable);

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $v1->id));
        $this->assertTrue($result->contains('id', $v2->id));
    }

    public function testGetVideosByIdsFromPoolReturnsOnlyMatchingVideos(): void
    {
        $v1 = Video::factory()->create();
        $v2 = Video::factory()->create();
        $v3 = Video::factory()->create();

        $pool = collect([$v1, $v2, $v3]);
        $ids = [$v1->getKey(), $v3->getKey()];

        $result = $this->videoRepository->getVideosByIdsFromPool($pool, $ids);

        $this->assertCount(2, $result);
        $this->assertEqualsCanonicalizing(
            [$v1->getKey(), $v3->getKey()],
            $result->pluck('id')->all()
        );
    }

    public function testGetVideosByIdsFromPoolReturnsEmptyWhenNoneMatch(): void
    {
        $v1 = Video::factory()->create();
        $v2 = Video::factory()->create();

        $pool = collect([$v1, $v2]);
        $ids = [999, 888];

        $result = $this->videoRepository->getVideosByIdsFromPool($pool, $ids);

        $this->assertCount(0, $result);
    }

    public function testGetVideosByIdsFromPoolIgnoresIdsNotInPool(): void
    {
        $v1 = Video::factory()->create();
        $v2 = Video::factory()->create();
        $vOther = Video::factory()->create();

        $pool = collect([$v1, $v2]);
        $ids = [$v1->getKey(), $vOther->getKey()];

        $result = $this->videoRepository->getVideosByIdsFromPool($pool, $ids);

        $this->assertCount(1, $result);
        $this->assertSame($v1->getKey(), $result->first()->getKey());
    }

    public function testGetVideosByIdsFromPoolPreservesPoolOrder(): void
    {
        $v1 = Video::factory()->create();
        $v2 = Video::factory()->create();
        $v3 = Video::factory()->create();

        // absichtlich gemischt
        $pool = collect([$v3, $v1, $v2]);

        // nur diese sollen zurückkommen – aber in Pool-Reihenfolge
        $ids = [$v1->getKey(), $v2->getKey()];

        $result = $this->videoRepository->getVideosByIdsFromPool($pool, $ids);

        $this->assertSame(
            [$v1->getKey(), $v2->getKey()],
            $result->pluck('id')->all()
        );
    }
}