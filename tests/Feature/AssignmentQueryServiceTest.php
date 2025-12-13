<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Services\Queries\AssignmentQueryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AssignmentQueryInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AssignmentQueryInterface::class);
    }

    public function testItFiltersByChannel(): void
    {
        $batch = Batch::factory()->create();
        $channelA = Channel::factory()->create();
        $channelB = Channel::factory()->create();
        Assignment::factory()->forBatch($batch)->forChannel($channelA)->create();
        Assignment::factory()->forBatch($batch)->forChannel($channelB)->create();

        $ids = $this->service->forChannel($channelA)->pluck('channel_id')->unique();

        $this->assertEquals([$channelA->getKey()], $ids->all());
    }

    public function testAvailableExcludesExpired(): void
    {
        $batch = Batch::factory()->create();
        Assignment::factory()->forBatch($batch)->queued()->create();
        Assignment::factory()->forBatch($batch)->queued()->expired()->create();

        $this->assertSame(1, $this->service->available()->count());
    }

    public function testDownloadedMatchesStatus(): void
    {
        $batch = Batch::factory()->create();
        Assignment::factory()->forBatch($batch)->queued()->create();
        Assignment::factory()->forBatch($batch)->state(['status' => StatusEnum::PICKEDUP->value])->create();

        $this->assertSame(1, $this->service->downloaded()->count());
    }

    public function testExpiredIncludesPastAndFlagged(): void
    {
        $batch = Batch::factory()->create();
        Assignment::factory()->forBatch($batch)->queued()->expired()->create();
        Assignment::factory()->forBatch($batch)->state(['status' => StatusEnum::EXPIRED->value])->create();
        Assignment::factory()->forBatch($batch)->queued()->create();

        $this->assertSame(2, $this->service->expired()->count());
    }

    public function testReturnedFiltersRejected(): void
    {
        $batch = Batch::factory()->create();
        Assignment::factory()->forBatch($batch)->queued()->create();
        Assignment::factory()->forBatch($batch)->state(['status' => StatusEnum::REJECTED->value])->create();

        $this->assertSame(1, $this->service->returned()->count());
    }
}
