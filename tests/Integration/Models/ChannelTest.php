<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Assignment;
use App\Models\Channel;
use App\Models\ChannelVideoBlock;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Tests\DatabaseTestCase;

final class ChannelTest extends DatabaseTestCase
{
    public function testScopesAndRelationships(): void
    {
        $activeChannel = Channel::factory()->create([
            'is_video_reception_paused' => false,
        ]);
        $pausedChannel = Channel::factory()->create([
            'is_video_reception_paused' => true,
        ]);

        Assignment::factory()->forChannel($activeChannel)->withBatch()->create();
        $block = ChannelVideoBlock::factory()->forChannel($activeChannel)->create();

        $activeBlocks = $activeChannel->activeVideoBlocks;

        $this->assertTrue(Channel::query()->isActive()->get()->contains($activeChannel));
        $this->assertTrue($activeChannel->assignments->first()->channel->is($activeChannel));
        $this->assertCount(1, $activeChannel->videoBlocks);
        $this->assertTrue($activeBlocks->first()->is($block));
        $this->assertTrue($activeChannel->blockedVideos->first()->is($block->video));
        $this->assertTrue($activeBlocks->every(fn(ChannelVideoBlock $b) => $b->until->greaterThan(now())));
        $this->assertTrue($pausedChannel->is_video_reception_paused);
    }

    public function testApprovalHelpersReturnSignedData(): void
    {
        $channel = Channel::factory()->create([
            'email' => 'creator@example.com',
        ]);

        $expectedToken = hash('sha256', $channel->email.config('app.key'));

        $this->assertSame($expectedToken, $channel->getApprovalToken());

        $approvalUrl = $channel->getApprovalUrl();
        $this->assertTrue(str_contains($approvalUrl, (string)$channel->getKey()));
        $this->assertTrue(str_contains($approvalUrl, $expectedToken));
        $this->assertSame(URL::route('channels.approve', ['channel' => $channel, 'token' => $expectedToken]),
            $approvalUrl);
    }

    public function testAssignUserToChannel(): void
    {
        $channel = Channel::factory()->create();
        $user = User::factory()->create();

        $channel->channelUsers()->attach($user->getKey());

        $this->assertTrue($channel->channelUsers()->first()->is($user));
    }
}
