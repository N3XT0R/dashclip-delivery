<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Enum\ProcessingStatusEnum;
use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Channel;
use App\Repository\AssignmentRepository;
use Tests\DatabaseTestCase;

/**
 * A channel may fetch an offer again while its video file is still stored.
 */
final class AssignmentRepositoryDownloadableTest extends DatabaseTestCase
{
    public function testADeletedVideoStaysDownloadableUntilItsFilesAreRemoved(): void
    {
        $channel = Channel::factory()->create();
        $assignment = Assignment::factory()->forChannel($channel)->create([
            'status' => StatusEnum::PICKEDUP->value,
            'expires_at' => now()->addWeek(),
        ]);
        $assignment->video->delete();

        $found = app(AssignmentRepository::class)
            ->fetchDownloadableForChannel($channel, collect([$assignment->getKey()]))
            ->modelKeys();

        self::assertContains($assignment->getKey(), $found);
    }

    public function testAVideoWhoseFilesAreGoneIsNotDownloadableAnyMore(): void
    {
        $channel = Channel::factory()->create();
        $assignment = Assignment::factory()->forChannel($channel)->create([
            'status' => StatusEnum::PICKEDUP->value,
            'expires_at' => now()->addWeek(),
        ]);
        $assignment->video->update(['processing_status' => ProcessingStatusEnum::Deleted]);
        $assignment->video->delete();

        $found = app(AssignmentRepository::class)
            ->fetchDownloadableForChannel($channel, collect([$assignment->getKey()]))
            ->modelKeys();

        self::assertNotContains($assignment->getKey(), $found);
    }
}
