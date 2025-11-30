<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Download;
use App\Models\Notification;
use App\Models\User;
use App\Models\Video;
use Tests\DatabaseTestCase;

final class AssignmentTest extends DatabaseTestCase
{
    public function testBelongsToVideoChannelBatchAndHasDownloads(): void
    {
        $batch = Batch::factory()->create();
        $video = Video::factory()->create();
        $channel = Channel::factory()->create();

        $assignment = Assignment::factory()
            ->forVideo($video)
            ->forChannel($channel)
            ->withBatch($batch)
            ->has(Download::factory()->count(2))
            ->create();

        $this->assertTrue($assignment->video->is($video));
        $this->assertTrue($assignment->channel->is($channel));
        $this->assertTrue($assignment->batch->is($batch));
        $this->assertCount(2, $assignment->downloads);
    }

    public function testHasUsersClipsScopeReturnsAssignments(): void
    {
        $user = User::factory()->create();
        $videoWithClip = Video::factory()->create();
        $videoWithoutClip = Video::factory()->create();
        $batch = Batch::factory()->create();

        $clip = $videoWithClip->clips()->create([
            'start_sec' => 0,
            'end_sec' => 10,
        ]);
        $clip->setUser($user)->save();

        $assignmentWithClip = Assignment::factory()->forVideo($videoWithClip)->withBatch($batch)->create();
        Assignment::factory()->forVideo($videoWithoutClip)->withBatch($batch)->create();

        $found = Assignment::query()->hasUsersClips($user)->get();

        $this->assertCount(1, $found);
        $this->assertTrue($found->first()->is($assignmentWithClip));
    }

    public function testSetExpiresAtAndSetNotifiedMutateAttributes(): void
    {
        $assignment = Assignment::factory()->create([
            'expires_at' => null,
            'status' => StatusEnum::QUEUED->value,
            'last_notified_at' => null,
            'batch_id' => Batch::factory()->create(),
        ]);

        $assignment->setExpiresAt(2);
        $this->assertNotNull($assignment->expires_at);
        $this->assertTrue($assignment->expires_at->greaterThan(now()->addHours(23)));

        $assignment->setNotified();

        $this->assertSame(StatusEnum::NOTIFIED->value, $assignment->status);
        $this->assertNotNull($assignment->last_notified_at);
    }

    public function testBelongsToNotification(): void
    {
        $notification = Notification::factory()->create();

        $assignment = Assignment::factory()
            ->state(['notification_id' => $notification->getKey()])
            ->withBatch()
            ->create();

        $this->assertTrue($assignment->notification->is($notification));
    }
}
