<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Models\Clip;
use App\Models\Video;
use App\Repository\ClipRepository;
use Tests\DatabaseTestCase;

class ClipRepositoryTest extends DatabaseTestCase
{
    protected ClipRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(ClipRepository::class);
    }

    public function testGetBundleKeysByVideoIdsReturnsUniqueKeys(): void
    {
        // Arrange
        $videos = Video::factory()->count(4)->create();

        Clip::factory()->forVideo($videos[0])->withBundleKey('bundle-A')->create();
        Clip::factory()->forVideo($videos[1])->withBundleKey('bundle-B')->create();
        Clip::factory()->forVideo($videos[2])->withBundleKey('bundle-A')->create(); // duplicate
        Clip::factory()->forVideo($videos[3])->withBundleKey(null)->create();       // ignored

        // Act
        $videoIds = $videos->pluck('id')->all();
        $result = $this->repository->getBundleKeysByVideoIds($videoIds);

        // Assert
        $this->assertCount(3, $result);
        $this->assertTrue($result->contains('bundle-A'));
        $this->assertTrue($result->contains('bundle-B'));
    }

    public function testGetBundleVideoIdsByBundleKeysReturnsUniqueVideoIds(): void
    {
        // Arrange
        $videos = Video::factory()->count(4)->create();

        Clip::factory()->forVideo($videos[0])->withBundleKey('bundle-X')->create();
        Clip::factory()->forVideo($videos[1])->withBundleKey('bundle-Y')->create();
        Clip::factory()->forVideo($videos[2])->withBundleKey('bundle-X')->create(); // duplicate bundle
        Clip::factory()->forVideo($videos[3])->withBundleKey(null)->create();       // ignored

        // Act
        $result = $this->repository->getBundleVideoIdsByBundleKeys(['bundle-X', 'bundle-Y']);

        // Assert
        $this->assertCount(3, $result);
        $this->assertTrue($result->contains($videos[0]->id));
        $this->assertTrue($result->contains($videos[1]->id));
        $this->assertTrue($result->contains($videos[2]->id));
    }

    public function testEmptyResultsWhenNoMatchesFound(): void
    {
        // Act
        $bundleKeys = $this->repository->getBundleKeysByVideoIds([999]);
        $videoIds = $this->repository->getBundleVideoIdsByBundleKeys(['non-existent']);

        // Assert
        $this->assertTrue($bundleKeys->isEmpty());
        $this->assertTrue($videoIds->isEmpty());
    }
}
