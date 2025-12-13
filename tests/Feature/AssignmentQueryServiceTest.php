<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enum\StatusEnum;
use App\Models\Assignment;
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

    public function test_it_filters_by_channel(): void
    {
        $channelA = Channel::factory()->create();
        $channelB = Channel::factory()->create();
        Assignment::factory()->forChannel($channelA)->create();
        Assignment::factory()->forChannel($channelB)->create();

        $ids = $this->service->forChannel($channelA)->pluck('channel_id')->unique();

        $this->assertEquals([$channelA->getKey()], $ids->all());
    }

    public function test_available_excludes_expired(): void
    {
        Assignment::factory()->queued()->create();
        Assignment::factory()->queued()->expired()->create();

        $this->assertSame(1, $this->service->available()->count());
    }

    public function test_downloaded_matches_status(): void
    {
        Assignment::factory()->queued()->create();
        Assignment::factory()->state(['status' => StatusEnum::PICKEDUP->value])->create();

        $this->assertSame(1, $this->service->downloaded()->count());
    }

    public function test_expired_includes_past_and_flagged(): void
    {
        Assignment::factory()->queued()->expired()->create();
        Assignment::factory()->state(['status' => StatusEnum::EXPIRED->value])->create();
        Assignment::factory()->queued()->create();

        $this->assertSame(2, $this->service->expired()->count());
    }

    public function test_returned_filters_rejected(): void
    {
        Assignment::factory()->queued()->create();
        Assignment::factory()->state(['status' => StatusEnum::REJECTED->value])->create();

        $this->assertSame(1, $this->service->returned()->count());
    }
}
