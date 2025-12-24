<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Enum\StatusEnum;
use App\Facades\Cfg;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Download;
use App\Models\Notification;
use App\Models\User;
use App\Models\Video;
use Carbon\Carbon;
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

    public function testSetExpiresAtMutateAttributesToMinDate(): void
    {
        $expiresAt = now()->addDays(3);
        $assignment = Assignment::factory()->create([
            'expires_at' => $expiresAt,
            'status' => StatusEnum::QUEUED->value,
            'last_notified_at' => null,
            'batch_id' => Batch::factory()->create(),
        ]);

        Carbon::setTestNow($now = now());
        try {
            $assignment->setExpiresAt(2);
            $this->assertNotNull($assignment->expires_at);
            $expectedExpiry = $now->copy()->addDays(2)->endOfDay();
            $this->assertTrue($assignment->expires_at->isSameSecond($expectedExpiry));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function testSetExpiresAtMutateAttributesToConfigTTLDate(): void
    {
        $value = Cfg::get('expire_after_days', 'default', 6);
        $assignment = Assignment::factory()->create([
            'expires_at' => null,
            'status' => StatusEnum::QUEUED->value,
            'last_notified_at' => null,
            'batch_id' => Batch::factory()->create(),
        ]);

        Carbon::setTestNow($now = now());
        try {
            $assignment->setExpiresAt();
            $this->assertNotNull($assignment->expires_at);
            $expectedExpiry = $now->copy()->addDays($value)->endOfDay();
            $this->assertTrue($assignment->expires_at->isSameSecond($expectedExpiry));
        } finally {
            Carbon::setTestNow();
        }
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

    public function testHasChannelIdsScopeReturnsOnlyMatchingAssignments(): void
    {
        $channelA = Channel::factory()->create();
        $channelB = Channel::factory()->create();

        $assignmentA = Assignment::factory()
            ->forChannel($channelA)
            ->withBatch()
            ->create();

        Assignment::factory()
            ->forChannel($channelB)
            ->withBatch()
            ->create();

        $found = Assignment::query()
            ->hasChannelIds([$channelA->getKey()])
            ->get();

        $this->assertCount(1, $found);
        $this->assertTrue($found->first()->is($assignmentA));
    }

    public function testAvailableScopeReturnsOnlyQueuedOrNotifiedAndNotExpired(): void
    {
        Carbon::setTestNow($now = now());

        try {
            $availableQueued = Assignment::factory()->create([
                'status' => StatusEnum::QUEUED->value,
                'expires_at' => null,
            ]);

            $availableNotified = Assignment::factory()->create([
                'status' => StatusEnum::NOTIFIED->value,
                'expires_at' => $now->copy()->addDay(),
            ]);

            Assignment::factory()->create([
                'status' => StatusEnum::EXPIRED->value,
                'expires_at' => $now->copy()->subDay(),
            ]);

            Assignment::factory()->create([
                'status' => StatusEnum::QUEUED->value,
                'expires_at' => $now->copy()->subDay(),
            ]);

            $found = Assignment::query()->available()->get();

            $this->assertTrue($found->contains($availableQueued));
            $this->assertTrue($found->contains($availableNotified));
            $this->assertCount(2, $found);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function testDownloadedScopeReturnsPickedUpAssignmentsWithDownloadsOrderedByLatest(): void
    {
        $assignmentOlder = Assignment::factory()->create([
            'status' => StatusEnum::PICKEDUP->value,
        ]);

        Download::factory()->create([
            'assignment_id' => $assignmentOlder->getKey(),
            'downloaded_at' => now()->subDay(),
        ]);

        $assignmentNewer = Assignment::factory()->create([
            'status' => StatusEnum::PICKEDUP->value,
        ]);

        Download::factory()->create([
            'assignment_id' => $assignmentNewer->getKey(),
            'downloaded_at' => now(),
        ]);

        Assignment::factory()->create([
            'status' => StatusEnum::QUEUED->value,
        ]);

        $found = Assignment::query()->downloaded()->get();

        $this->assertCount(2, $found);
        $this->assertTrue($found->first()->is($assignmentNewer));
        $this->assertTrue($found->last()->is($assignmentOlder));
    }

    public function testExpiredScopeReturnsOnlyExpiredAssignmentsOrderedByUpdatedAt(): void
    {
        $older = Assignment::factory()->create([
            'status' => StatusEnum::EXPIRED->value,
            'updated_at' => now()->subDay(),
        ]);

        $newer = Assignment::factory()->create([
            'status' => StatusEnum::EXPIRED->value,
            'updated_at' => now(),
        ]);

        Assignment::factory()->create([
            'status' => StatusEnum::QUEUED->value,
        ]);

        $found = Assignment::query()->expired()->get();

        $this->assertCount(2, $found);
        $this->assertTrue($found->first()->is($newer));
        $this->assertTrue($found->last()->is($older));
    }

    public function testReturnedScopeReturnsOnlyRejectedAssignmentsOrderedByUpdatedAt(): void
    {
        $older = Assignment::factory()->create([
            'status' => StatusEnum::REJECTED->value,
            'updated_at' => now()->subDay(),
        ]);

        $newer = Assignment::factory()->create([
            'status' => StatusEnum::REJECTED->value,
            'updated_at' => now(),
        ]);

        Assignment::factory()->create([
            'status' => StatusEnum::QUEUED->value,
        ]);

        $found = Assignment::query()->returned()->get();

        $this->assertCount(2, $found);
        $this->assertTrue($found->first()->is($newer));
        $this->assertTrue($found->last()->is($older));
    }

}
