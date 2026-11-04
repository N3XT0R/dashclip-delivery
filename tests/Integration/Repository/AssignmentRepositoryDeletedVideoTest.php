<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\User;
use App\Repository\AssignmentRepository;
use Tests\DatabaseTestCase;

/**
 * Offers of deleted videos stay as history but must not reach the offer pages or the API any more.
 */
final class AssignmentRepositoryDeletedVideoTest extends DatabaseTestCase
{
    public function testPendingOffersLeaveOutDeletedVideos(): void
    {
        [$batch, $channel, $kept, $hidden] = $this->offers(StatusEnum::QUEUED->value);

        $ids = app(AssignmentRepository::class)->fetchPending($batch, $channel)->modelKeys();

        self::assertContains($kept->getKey(), $ids);
        self::assertNotContains($hidden->getKey(), $ids);
    }

    public function testPickedUpOffersLeaveOutDeletedVideos(): void
    {
        [$batch, $channel, $kept, $hidden] = $this->offers(StatusEnum::PICKEDUP->value);

        $ids = app(AssignmentRepository::class)->fetchPickedUp($batch, $channel)->modelKeys();

        self::assertContains($kept->getKey(), $ids);
        self::assertNotContains($hidden->getKey(), $ids);
    }

    public function testVisibleOffersLeaveOutDeletedVideos(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user->getKey(), ['is_user_verified' => true]);
        $kept = Assignment::factory()->forChannel($channel)->create();
        $hidden = Assignment::factory()->forChannel($channel)->create();
        $hidden->video->delete();

        $ids = app(AssignmentRepository::class)->visibleForUser($user)->pluck('id')->all();

        self::assertContains($kept->getKey(), $ids);
        self::assertNotContains($hidden->getKey(), $ids);
    }

    /**
     * @return array{0: Batch, 1: Channel, 2: Assignment, 3: Assignment}
     */
    private function offers(string $status): array
    {
        $batch = Batch::factory()->type('assign')->create();
        $channel = Channel::factory()->create();
        $kept = Assignment::factory()->forChannel($channel)->withBatch($batch)->create(['status' => $status]);
        $hidden = Assignment::factory()->forChannel($channel)->withBatch($batch)->create(['status' => $status]);
        $hidden->video->delete();

        return [$batch, $channel, $kept, $hidden];
    }
}
