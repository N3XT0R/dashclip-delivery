<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Models\Clip;
use App\Models\User;
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

    public function testGetBundleVideoIdsByBundleKeysEmptyResultsWhenNoMatchesFound(): void
    {
        // Act
        $bundleKeys = $this->repository->getBundleKeysByVideoIds([999]);
        $videoIds = $this->repository->getBundleVideoIdsByBundleKeys(['non-existent']);

        // Assert
        $this->assertTrue($bundleKeys->isEmpty());
        $this->assertTrue($videoIds->isEmpty());
    }

    public function testGetClipsWhereUserIdIsNullReturnsOnlyClipsWhereUserIdIsNullAndSubmittedByIsNonEmpty(): void
    {
        // Valid clip: user_id null + submitted_by not empty
        $valid = Clip::factory()->create([
            'user_id' => null,
            'submitted_by' => 'SomeUploader',
        ]);

        // Invalid: user_id not null
        $user = User::factory()->create();
        $withUserId = Clip::factory()->create([
            'user_id' => $user->getKey(),
            'submitted_by' => 'UploaderX',
        ]);

        // Invalid: submitted_by missing
        $submittedByNull = Clip::factory()->create([
            'user_id' => null,
            'submitted_by' => null,
        ]);

        // Invalid: submitted_by empty
        $submittedByEmpty = Clip::factory()->create([
            'user_id' => null,
            'submitted_by' => '',
        ]);

        // Act
        $result = $this->repository->getClipsWhereUserIdIsNull();

        // Assert: result contains the valid clip
        $this->assertTrue(
            $result->contains(fn(Clip $c) => $c->getKey() === $valid->getKey()),
            'Expected valid clip to be returned.'
        );

        // Assert: invalid clips are not included
        $this->assertFalse($result->contains($withUserId));
        $this->assertFalse($result->contains($submittedByNull));
        $this->assertFalse($result->contains($submittedByEmpty));
    }

    public function testGetClipsWhereUserIdIsNullReturnsMultipleValidClips(): void
    {
        $c1 = Clip::factory()->create([
            'user_id' => null,
            'submitted_by' => 'A',
        ]);

        $c2 = Clip::factory()->create([
            'user_id' => null,
            'submitted_by' => 'B',
        ]);

        $user = User::factory()->create();

        // noise
        Clip::factory()->create([
            'user_id' => $user,
            'submitted_by' => 'C',
        ]);

        // Act
        $result = $this->repository->getClipsWhereUserIdIsNull();

        // Assert count
        $this->assertCount(2, $result, 'Expected exactly two valid clips.');

        // Assert IDs match
        $this->assertSame(
            [$c1->getKey(), $c2->getKey()],
            $result->pluck('id')->sort()->values()->all()
        );
    }

    public function testGetClipsWhereUserIdIsNullReturnsEmptyCollectionWhenNoMatches(): void
    {
        // invalid: user_id not null
        $user = User::factory()->create();
        Clip::factory()->create([
            'user_id' => $user->getKey(),
            'submitted_by' => 'X',
        ]);

        // invalid: submitted_by empty
        Clip::factory()->create([
            'user_id' => null,
            'submitted_by' => '',
        ]);

        $result = $this->repository->getClipsWhereUserIdIsNull();

        $this->assertTrue($result->isEmpty(), 'Expected empty result set.');
    }

}
