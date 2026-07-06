<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enum\BatchTypeEnum;
use App\Enum\ProcessingStatusEnum;
use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\User;
use App\Models\Video;
use App\Repository\AssignmentRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Tests\DatabaseTestCase;

/**
 * Feature tests for the "assign:distribute" command using the real distributor.
 * No mocking/faking of services; we assert DB side-effects instead of brittle output.
 */
final class AssignDistributeTest extends DatabaseTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Happy path: with two eligible channels and two videos, the command should
     * create an "assign" batch and queued assignments for our entities.
     * We avoid asserting exact console text to keep the test robust.
     */
    public function testDistributesAndCreatesQueuedAssignments(): void
    {
        // Arrange: two eligible channels
        $ch1 = Channel::factory()->create(['weekly_quota' => 10, 'weight' => 1]);
        $ch2 = Channel::factory()->create(['weekly_quota' => 10, 'weight' => 1]);

        // Arrange: two new videos
        $v1 = Video::factory()->create([
            'processing_status' => ProcessingStatusEnum::Completed
        ]);
        $v2 = Video::factory()->create([
            'processing_status' => ProcessingStatusEnum::Completed
        ]);

        // Act: run the real command
        $this->artisan('assign:distribute')
            ->assertExitCode(Command::SUCCESS);

        // Assert: a new assign batch exists and is finalized
        $batch = Batch::query()->where('type', 'assign')->latest('id')->first();
        $this->assertNotNull($batch, 'Expected a new assign batch to be created.');
        $this->assertNotNull($batch->started_at);
        $this->assertNotNull($batch->finished_at);
        $this->assertIsArray($batch->stats);

        // Assert: at least one assignment for this batch
        $created = Assignment::query()->where('batch_id', $batch->getKey())->get();
        $this->assertGreaterThanOrEqual(1, $created->count(), 'Expected at least one assignment to be created.');

        // All created assignments must reference our videos and be queued.
        // (We do NOT assert the exact channel used to keep the test robust against distribution strategy.)
        $videoIds = [$v1->getKey(), $v2->getKey()];
        foreach ($created as $a) {
            $this->assertContains($a->video_id, $videoIds, 'Unexpected video assigned.');
            $this->assertSame('queued', $a->status, 'Expected new assignments to be queued.');
        }
    }

    public function testDistributePrioritizesChannelsWithLowerWeeklyUsageAcrossBatches(): void
    {
        Carbon::setTestNow('2026-07-06 08:00:00');
        Channel::query()->delete();

        $channels = collect([
            Channel::factory()->create(['weekly_quota' => 5, 'weight' => 1]),
            Channel::factory()->create(['weekly_quota' => 5, 'weight' => 1]),
            Channel::factory()->create(['weekly_quota' => 5, 'weight' => 1]),
            Channel::factory()->create(['weekly_quota' => 5, 'weight' => 1]),
        ]);

        $previousBatch = Batch::factory()->type(BatchTypeEnum::ASSIGN->value)->finished()->create();
        $assignedThisWeek = now()->startOfWeek()->addDay();

        foreach ($channels->take(2) as $channel) {
            Assignment::factory()
                ->forChannel($channel)
                ->forVideo(Video::factory()->create())
                ->withBatch($previousBatch)
                ->create([
                    'status' => StatusEnum::PICKEDUP->value,
                    'created_at' => $assignedThisWeek,
                    'updated_at' => $assignedThisWeek,
                ]);
        }

        $weeklyAssignmentCounts = app(AssignmentRepository::class)->countAssignmentsByChannelSince(now()->startOfWeek());
        $this->assertSame(1, $weeklyAssignmentCounts->get($channels[0]->getKey()));
        $this->assertSame(1, $weeklyAssignmentCounts->get($channels[1]->getKey()));
        $this->assertNull($weeklyAssignmentCounts->get($channels[2]->getKey()));
        $this->assertNull($weeklyAssignmentCounts->get($channels[3]->getKey()));

        $uploader = User::factory()->create();
        $videos = Video::factory()
            ->count(2)
            ->create(['processing_status' => ProcessingStatusEnum::Completed]);

        $videos->each(fn (Video $video) => Clip::factory()->for($video)->forUser($uploader)->create());

        $this->artisan('assign:distribute')
            ->assertExitCode(Command::SUCCESS);

        $batch = Batch::query()->where('type', BatchTypeEnum::ASSIGN->value)->latest('id')->first();

        $assignedChannelIds = Assignment::query()
            ->where('batch_id', $batch?->getKey())
            ->whereIn('video_id', $videos->pluck('id'))
            ->pluck('channel_id')
            ->all();

        $this->assertEqualsCanonicalizing(
            $channels->slice(2)->pluck('id')->all(),
            $assignedChannelIds
        );
    }

    /**
     * No eligible work: many distributors treat this as an error and throw,
     * so the command returns FAILURE. We only assert that no assignments were created.
     */
    public function testRunsWithNoEligibleWorkReturnsFailureAndCreatesNoAssignments(): void
    {
        // Arrange: no channels, no videos

        // Act & Assert: command fails gracefully
        $this->artisan('assign:distribute')
            ->assertExitCode(Command::FAILURE);

        // Assert: no assignments created at all
        $this->assertSame(0, Assignment::query()->count());
        // Optional/loose: do not assert on batch presence, as implementations differ
    }
}
