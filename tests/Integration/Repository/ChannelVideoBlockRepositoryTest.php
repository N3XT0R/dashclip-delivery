<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Models\Channel;
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
        Channel::query()->delete();

        Carbon::setTestNow('2025-01-01 12:00:00');
    }

    private function createChannel(): Channel
    {
        return Channel::factory()->create([
            'weekly_quota' => 10,
            'weight' => 1,
        ]);
    }

    public function testLoadsActiveBlocksForVideosInPool(): void
    {
        $video = Video::factory()->create();
        $channel = $this->createChannel();
        $pool = collect([$video]);

        ChannelVideoBlock::factory()->create([
            'video_id' => $video->getKey(),
            'channel_id' => $channel->getKey(),
            'until' => now()->addHour(),
        ]);

        $result = $this->repository->preloadActiveBlocks($pool);

        $this->assertArrayHasKey($video->getKey(), $result);
        $this->assertEquals(
            [$channel->getKey()],
            $result[$video->getKey()]->values()->all()
        );
    }

    public function testIgnoresExpiredBlocks(): void
    {
        $video = Video::factory()->create();
        $channel = $this->createChannel();
        $pool = collect([$video]);

        ChannelVideoBlock::factory()->create([
            'video_id' => $video->getKey(),
            'channel_id' => $channel->getKey(),
            'until' => now()->subHour(),
        ]);

        $result = $this->repository->preloadActiveBlocks($pool);

        $this->assertArrayNotHasKey($video->getKey(), $result);
    }

    public function testIgnoresVideosWithoutBlocks(): void
    {
        $video = Video::factory()->create();
        $pool = collect([$video]);

        $result = $this->repository->preloadActiveBlocks($pool);

        $this->assertArrayNotHasKey($video->getKey(), $result);
    }

    public function testLoadsOnlyActiveBlocksWhenMixed(): void
    {
        $video = Video::factory()->create();
        $pool = collect([$video]);

        $activeChannel = $this->createChannel();
        $expiredChannel = $this->createChannel();

        ChannelVideoBlock::factory()->create([
            'video_id' => $video->getKey(),
            'channel_id' => $activeChannel->getKey(),
            'until' => now()->addMinutes(10),
        ]);

        ChannelVideoBlock::factory()->create([
            'video_id' => $video->getKey(),
            'channel_id' => $expiredChannel->getKey(),
            'until' => now()->subMinute(),
        ]);

        $result = $this->repository->preloadActiveBlocks($pool);

        $this->assertArrayHasKey($video->getKey(), $result);
        $this->assertEquals(
            [$activeChannel->getKey()],
            $result[$video->getKey()]->values()->all()
        );
    }

    public function testCollapsesDuplicateChannelIds(): void
    {
        $video = Video::factory()->create();
        $pool = collect([$video]);

        $channel1 = $this->createChannel();
        $channel2 = $this->createChannel();

        // Zwei Blocks: andere channel_id, gleiche video_id
        ChannelVideoBlock::factory()->create([
            'video_id' => $video->getKey(),
            'channel_id' => $channel1->getKey(),
            'until' => now()->addHour(),
        ]);

        ChannelVideoBlock::factory()->create([
            'video_id' => $video->getKey(),
            'channel_id' => $channel2->getKey(),
            'until' => now()->addMinutes(5),
        ]);

        $result = $this->repository->preloadActiveBlocks($pool);

        $this->assertArrayHasKey($video->getKey(), $result);

        $this->assertEqualsCanonicalizing(
            [$channel1->getKey(), $channel2->getKey()],
            $result[$video->getKey()]->values()->all()
        );
    }


    public function testIgnoresBlocksForVideosNotInPool(): void
    {
        $videoInPool = Video::factory()->create();
        $videoOther = Video::factory()->create();
        $pool = collect([$videoInPool]);

        $channel = $this->createChannel();

        ChannelVideoBlock::factory()->create([
            'video_id' => $videoOther->getKey(),
            'channel_id' => $channel->getKey(),
            'until' => now()->addHour(),
        ]);

        $result = $this->repository->preloadActiveBlocks($pool);

        $this->assertArrayNotHasKey($videoOther->getKey(), $result);
    }
}
