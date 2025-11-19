<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Models\Clip;
use App\Models\User;
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

    public function testPartitionByUploaderGroupsVideosByUploader(): void
    {
        // uploader A
        $userA = User::factory()->standard()->create();
        $v1 = Video::factory()->create();
        $v2 = Video::factory()->create();
        Clip::factory()->forVideo($v1)->create(['user_id' => $userA->getKey()]);
        Clip::factory()->forVideo($v2)->create(['user_id' => $userA->getKey()]);

        // uploader B
        $userB = User::factory()->standard()->create();
        $v3 = Video::factory()->create();
        Clip::factory()->forVideo($v3)->create(['user_id' => $userB->getKey()]);

        // uploader unknown → has clip with null user_id
        $v4 = Video::factory()->create();
        Clip::factory()->forVideo($v4)->create(['user_id' => null]);

        // uploader unknown → no clip at all
        $v5 = Video::factory()->create();

        $videos = collect([$v1, $v2, $v3, $v4, $v5]);

        // Act
        $result = $this->videoRepository->partitionByUploader($videos);

        // Assert: keys exist
        $this->assertArrayHasKey($userA->getKey(), $result);
        $this->assertArrayHasKey($userB->getKey(), $result);
        $this->assertArrayHasKey(0, $result);

        // uploader A → v1 + v2
        $this->assertCount(2, $result[$userA->getKey()]);
        $this->assertTrue($result[$userA->getKey()]->contains('id', $v1->getKey()));
        $this->assertTrue($result[$userA->getKey()]->contains('id', $v2->getKey()));

        // uploader B → v3
        $this->assertCount(1, $result[$userB->getKey()]);
        $this->assertTrue($result[$userB->getKey()]->contains('id', $v3->getKey()));

        // unknown uploader (0) → v4 + v5
        $this->assertCount(2, $result[0]);
        $this->assertTrue($result[0]->contains('id', $v4->getKey()));
        $this->assertTrue($result[0]->contains('id', $v5->getKey()));
    }

}