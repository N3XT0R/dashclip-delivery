<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Assignment;
use App\Models\Channel;
use App\Models\ChannelVideoBlock;
use App\Models\Video;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
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

        $expectedToken = sha1($channel->email.config('app.key'));

        $this->assertSame($expectedToken, $channel->getApprovalToken());

        $approvalUrl = $channel->getApprovalUrl();
        $this->assertTrue(str_contains($approvalUrl, (string) $channel->getKey()));
        $this->assertTrue(str_contains($approvalUrl, $expectedToken));
        $this->assertSame(URL::route('channels.approve', ['channel' => $channel, 'token' => $expectedToken]), $approvalUrl);
    }

    public function testAssignedTeamsRelationship(): void
    {
        if (! Schema::hasTable('channel_user')) {
            Schema::create('channel_user', static function (Blueprint $table): void {
                $table->id();
                $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
            });
        }

        $channel = Channel::factory()->create();
        $user = \App\Models\User::factory()->create();

        $channel->assignedTeams()->attach($user->getKey());

        $this->assertTrue($channel->assignedTeams->first()->is($user));
    }
}
