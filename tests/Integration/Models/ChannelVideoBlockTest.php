<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Channel;
use App\Models\ChannelVideoBlock;
use App\Models\Video;
use Tests\DatabaseTestCase;

final class ChannelVideoBlockTest extends DatabaseTestCase
{
    public function testBelongsToChannelAndVideo(): void
    {
        $channel = Channel::factory()->create();
        $video = Video::factory()->create();

        $block = ChannelVideoBlock::factory()
            ->forChannel($channel)
            ->forVideo($video)
            ->create();

        $this->assertTrue($block->channel->is($channel));
        $this->assertTrue($block->video->is($video));
    }

    public function testActiveScopeReturnsNonExpiredBlocks(): void
    {
        $activeBlock = ChannelVideoBlock::factory()->create();
        ChannelVideoBlock::factory()->expired()->create();

        $found = ChannelVideoBlock::query()->active()->get();

        $this->assertCount(1, $found);
        $this->assertTrue($found->first()->is($activeBlock));
    }
}
