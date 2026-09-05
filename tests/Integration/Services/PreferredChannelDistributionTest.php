<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Enum\ProcessingStatusEnum;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\ChannelVideoBlock;
use App\Models\Clip;
use App\Models\Team;
use App\Models\Video;
use App\Services\AssignmentDistributor;
use Tests\DatabaseTestCase;

class PreferredChannelDistributionTest extends DatabaseTestCase
{
    private AssignmentDistributor $distributor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->distributor = app(AssignmentDistributor::class);
        // Start from a clean, deterministic channel set.
        Channel::query()->delete();
    }

    private function video(string $hash): Video
    {
        return Video::create([
            'hash' => $hash,
            'path' => $hash,
            'processing_status' => ProcessingStatusEnum::Completed,
        ]);
    }

    public function testValidPreferredChannelWinsOverAlgorithm(): void
    {
        $wanted = Channel::factory()->create(['name' => 'Wanted', 'weight' => 1, 'weekly_quota' => 10]);
        $other = Channel::factory()->create(['name' => 'Other', 'weight' => 50, 'weekly_quota' => 10]);

        $video = $this->video('h1');
        Clip::create(['video_id' => $video->id, 'preferred_channel_id' => $wanted->getKey()]);

        $result = $this->distributor->distribute();

        $this->assertSame(1, $result['assigned']);
        $this->assertDatabaseHas('assignments', [
            'video_id' => $video->getKey(),
            'channel_id' => $wanted->getKey(),
            'via_preferred_channel' => true,
        ]);
        $this->assertDatabaseMissing('assignments', [
            'video_id' => $video->getKey(),
            'channel_id' => $other->getKey(),
        ]);
    }

    public function testNoPreferenceUsesAlgorithmAndFlagIsFalse(): void
    {
        Channel::factory()->create(['weekly_quota' => 10]);
        $video = $this->video('h2');
        Clip::create(['video_id' => $video->id]);

        $result = $this->distributor->distribute();

        $this->assertSame(1, $result['assigned']);
        $this->assertDatabaseHas('assignments', [
            'video_id' => $video->getKey(),
            'via_preferred_channel' => false,
        ]);
    }

    public function testPausedPreferredChannelFallsBackToAlgorithm(): void
    {
        $paused = Channel::factory()->paused()->create(['name' => 'Paused', 'weekly_quota' => 10]);
        $active = Channel::factory()->create(['name' => 'Active', 'weekly_quota' => 10]);

        $video = $this->video('h3');
        // preferred_channel_id can still point at the paused channel from an earlier import;
        // the distributor must not use it because it is not in the pool.
        Clip::create(['video_id' => $video->id, 'preferred_channel_id' => $paused->getKey()]);

        $result = $this->distributor->distribute();

        $this->assertSame(1, $result['assigned']);
        $this->assertDatabaseHas('assignments', [
            'video_id' => $video->getKey(),
            'channel_id' => $active->getKey(),
            'via_preferred_channel' => false,
        ]);
    }

    public function testExhaustedQuotaOnPreferredChannelDefersInsteadOfReassigning(): void
    {
        $wanted = Channel::factory()->create(['name' => 'Wanted', 'weight' => 1, 'weekly_quota' => 0]);
        $other = Channel::factory()->create(['name' => 'Other', 'weight' => 1, 'weekly_quota' => 10]);

        $video = $this->video('h4');
        Clip::create(['video_id' => $video->id, 'preferred_channel_id' => $wanted->getKey()]);

        $result = $this->distributor->distribute();

        $this->assertSame(0, $result['assigned']);
        $this->assertSame(1, $result['deferred']);
        $this->assertDatabaseMissing('assignments', ['video_id' => $video->getKey()]);

        // Next run with quota freed up → lands on the wished channel.
        $wanted->update(['weekly_quota' => 10]);
        $second = $this->distributor->distribute();

        $this->assertSame(1, $second['assigned']);
        $this->assertDatabaseHas('assignments', [
            'video_id' => $video->getKey(),
            'channel_id' => $wanted->getKey(),
            'via_preferred_channel' => true,
        ]);
    }

    public function testBundleGroupWithMatchingPreferenceGoesToWishedChannel(): void
    {
        $wanted = Channel::factory()->create(['name' => 'Wanted', 'weekly_quota' => 10]);
        Channel::factory()->create(['name' => 'Other', 'weight' => 99, 'weekly_quota' => 10]);

        $v1 = $this->video('h5a');
        $v2 = $this->video('h5b');
        Clip::create(['video_id' => $v1->id, 'bundle_key' => 'B', 'preferred_channel_id' => $wanted->getKey()]);
        Clip::create(['video_id' => $v2->id, 'bundle_key' => 'B', 'preferred_channel_id' => $wanted->getKey()]);

        $result = $this->distributor->distribute();

        $this->assertSame(2, $result['assigned']);
        $this->assertSame(
            [$wanted->getKey()],
            Assignment::query()->whereIn('video_id', [$v1->id, $v2->id])->pluck('channel_id')->unique()->values()->all()
        );
    }

    public function testBundleGroupWithConflictingPreferenceUsesAlgorithm(): void
    {
        $a = Channel::factory()->create(['name' => 'A', 'weekly_quota' => 10]);
        $b = Channel::factory()->create(['name' => 'B', 'weekly_quota' => 10]);

        $v1 = $this->video('h6a');
        $v2 = $this->video('h6b');
        Clip::create(['video_id' => $v1->id, 'bundle_key' => 'B', 'preferred_channel_id' => $a->getKey()]);
        Clip::create(['video_id' => $v2->id, 'bundle_key' => 'B', 'preferred_channel_id' => $b->getKey()]);

        $result = $this->distributor->distribute();

        $this->assertSame(2, $result['assigned']);
        foreach (Assignment::query()->whereIn('video_id', [$v1->id, $v2->id])->get() as $assignment) {
            $this->assertFalse($assignment->via_preferred_channel);
        }
    }

    public function testBlockedPreferredChannelDefersInsteadOfReassigning(): void
    {
        $wanted = Channel::factory()->create(['name' => 'Wanted', 'weekly_quota' => 10]);
        Channel::factory()->create(['name' => 'Other', 'weekly_quota' => 10]);

        $video = $this->video('h7');
        Clip::create(['video_id' => $video->id, 'preferred_channel_id' => $wanted->getKey()]);

        ChannelVideoBlock::create([
            'channel_id' => $wanted->getKey(),
            'video_id' => $video->getKey(),
            'until' => now()->addWeek(),
        ]);

        $result = $this->distributor->distribute();

        $this->assertSame(0, $result['assigned']);
        $this->assertSame(1, $result['deferred']);
        $this->assertDatabaseMissing('assignments', ['video_id' => $video->getKey()]);
    }

    public function testPreferredChannelOutsideTeamScopeFallsBackToAlgorithm(): void
    {
        $team = Team::factory()->create();

        $teamChannel = Channel::factory()->create(['name' => 'TeamChannel', 'weekly_quota' => 10]);
        $team->assignedChannels()->attach($teamChannel->getKey(), ['quota' => 10]);

        $nonTeamChannel = Channel::factory()->create(['name' => 'NonTeam', 'weekly_quota' => 10]);

        $video = $this->video('h8');
        $video->update(['team_id' => $team->getKey()]);
        Clip::create(['video_id' => $video->id, 'preferred_channel_id' => $nonTeamChannel->getKey()]);

        $result = $this->distributor->distribute();

        $this->assertSame(1, $result['assigned']);
        $this->assertDatabaseHas('assignments', [
            'video_id' => $video->getKey(),
            'channel_id' => $teamChannel->getKey(),
            'via_preferred_channel' => false,
        ]);
    }

    public function testPartialBundleAlreadyOnPreferredChannelFallsThroughNotDefersForever(): void
    {
        $wanted = Channel::factory()->create(['name' => 'Wanted', 'weekly_quota' => 10]);
        $other = Channel::factory()->create(['name' => 'Other', 'weekly_quota' => 10]);

        $v1 = $this->video('h9a');
        $v2 = $this->video('h9b');
        Clip::create(['video_id' => $v1->id, 'bundle_key' => 'B', 'preferred_channel_id' => $wanted->getKey()]);
        Clip::create(['video_id' => $v2->id, 'bundle_key' => 'B', 'preferred_channel_id' => $wanted->getKey()]);

        $first = $this->distributor->distribute();

        $this->assertSame(2, $first['assigned']);
        $this->assertSame(
            [$wanted->getKey()],
            Assignment::query()->whereIn('video_id', [$v1->id, $v2->id])
                ->pluck('channel_id')->unique()->values()->all()
        );
        $this->assertTrue(
            Assignment::query()->whereIn('video_id', [$v1->id, $v2->id])->get()
                ->every(fn (Assignment $a) => $a->via_preferred_channel === true)
        );

        // A third bundle-'B' video appears; expandBundles pulls v1+v2 back so the
        // group is [v1, v2, v3] with v1 & v2 already sitting on the wished channel.
        $v3 = $this->video('h9c');
        Clip::create(['video_id' => $v3->id, 'bundle_key' => 'B', 'preferred_channel_id' => $wanted->getKey()]);

        $second = $this->distributor->distribute();

        // Fix 1: fall through to the round-robin instead of deferring forever.
        $this->assertSame(0, $second['deferred']);
        $this->assertSame(3, $second['assigned']);
        $this->assertDatabaseHas('assignments', ['video_id' => $v3->getKey()]);

        // The round-robin placement lands the whole group on a fresh channel.
        $this->assertDatabaseHas('assignments', [
            'video_id' => $v3->getKey(),
            'channel_id' => $other->getKey(),
            'via_preferred_channel' => false,
        ]);
        $newAssignments = Assignment::query()
            ->whereIn('video_id', [$v1->id, $v2->id, $v3->id])
            ->where('channel_id', $other->getKey())
            ->get();
        $this->assertCount(3, $newAssignments);
        $this->assertTrue($newAssignments->every(fn (Assignment $a) => $a->via_preferred_channel === false));
    }
}
