<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Enum\ProcessingStatusEnum;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Clip;
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
}
