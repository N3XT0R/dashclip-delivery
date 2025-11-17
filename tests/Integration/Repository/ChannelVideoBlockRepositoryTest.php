<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Models\ChannelVideoBlock;
use App\Models\Video;
use App\Repository\ChannelVideoBlockRepository;
use Illuminate\Support\Carbon;
use Tests\DatabaseTestCase;

class ChannelVideoBlockRepositoryTest extends DatabaseTestCase
{
    private ChannelVideoBlockRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(ChannelVideoBlockRepository::class);
        Carbon::setTestNow('2025-01-01 12:00:00');
    }

    public function testPreloadActiveBlocksHandlesAllCombinations(): void
    {
        /**
         * Pool Videos
         */
        $v1 = Video::factory()->create(); // has active blocks
        $v2 = Video::factory()->create(); // only expired blocks
        $v3 = Video::factory()->create(); // no blocks
        $v4 = Video::factory()->create(); // mixed active + expired
        $v5 = Video::factory()->create(); // duplicates -> uniq

        // not in pool
        $otherVideo = Video::factory()->create();

        $pool = collect([$v1, $v2, $v3, $v4, $v5]);

        /**
         * --- Blocks ---
         */

        // v1: active blocks only
        ChannelVideoBlock::factory()->create([
            'video_id' => $v1->id,
            'channel_id' => 10,
            'until' => now()->addHour(),
        ]);
        ChannelVideoBlock::factory()->create([
            'video_id' => $v1->id,
            'channel_id' => 20,
            'until' => now()->addMinutes(30),
        ]);

        // v2: expired only -> must not appear
        ChannelVideoBlock::factory()->create([
            'video_id' => $v2->id,
            'channel_id' => 30,
            'until' => now()->subMinute(),
        ]);

        // v3: no blocks -> must not appear

        // v4: mixed -> only active
        ChannelVideoBlock::factory()->create([
            'video_id' => $v4->id,
            'channel_id' => 40,
            'until' => now()->addMinutes(10),
        ]);
        ChannelVideoBlock::factory()->create([
            'video_id' => $v4->id,
            'channel_id' => 41,
            'until' => now()->subMinute(),
        ]);

        // v5: duplicates
        ChannelVideoBlock::factory()->create([
            'video_id' => $v5->id,
            'channel_id' => 55,
            'until' => now()->addHour(),
        ]);
        ChannelVideoBlock::factory()->create([
            'video_id' => $v5->id,
            'channel_id' => 55,
            'until' => now()->addMinutes(5),
        ]);

        // not in pool: must be ignored
        ChannelVideoBlock::factory()->create([
            'video_id' => $otherVideo->id,
            'channel_id' => 99,
            'until' => now()->addHour(),
        ]);

        /**
         * Act
         */
        $result = $this->repository->preloadActiveBlocks($pool);

        /**
         * Assertions
         */

        // v1: two active
        $this->assertArrayHasKey($v1->id, $result);
        $this->assertEqualsCanonicalizing(
            [10, 20],
            $result[$v1->id]->all()
        );

        // v2: expired only -> no entry
        $this->assertArrayNotHasKey($v2->id, $result);

        // v3: no blocks -> no entry
        $this->assertArrayNotHasKey($v3->id, $result);

        // v4: mixed -> only active (channel 40)
        $this->assertArrayHasKey($v4->id, $result);
        $this->assertEquals([40], $result[$v4->id]->values()->all());

        // v5: duplicates -> must collapse to one
        $this->assertArrayHasKey($v5->id, $result);
        $this->assertEquals([55], $result[$v5->id]->values()->all());

        // not in pool -> must not appear
        $this->assertArrayNotHasKey($otherVideo->id, $result);
    }
}
