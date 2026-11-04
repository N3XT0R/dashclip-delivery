<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Constants\Config\DefaultConfigEntry;
use App\Enum\ProcessingStatusEnum;
use App\Enum\StatusEnum;
use App\Facades\Cfg;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\Video;
use Illuminate\Support\Carbon;
use Tests\DatabaseTestCase;

/**
 * An offer runs out exactly when the weekly run starts, because it was sent by the run a cycle
 * earlier. That run has to hand the video on right away instead of leaving it for another cycle.
 */
final class OfferCycleTimingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Channel::query()->delete();
        Cfg::set(DefaultConfigEntry::DISTRIBUTION_ROUNDS, 2, 'default', 'int');
        Carbon::setTestNow('2026-09-21 06:00:01');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testAnOfferRunningOutDuringTheRunIsExpiredByIt(): void
    {
        $offer = $this->offerRunningOutDuringTheRun();

        $this->artisan('assign:expire')->assertExitCode(0);

        self::assertSame(StatusEnum::EXPIRED->value, $offer->refresh()->status);
    }

    public function testTheSameRunHandsTheVideoToTheNextChannel(): void
    {
        $offer = $this->offerRunningOutDuringTheRun();
        $next = $this->channel('Next');

        $this->artisan('assign:expire')->assertExitCode(0);
        $this->artisan('assign:distribute')->assertExitCode(0);

        $this->assertDatabaseHas('assignments', [
            'video_id' => $offer->video_id,
            'channel_id' => $next->getKey(),
            'status' => StatusEnum::QUEUED->value,
        ]);
    }

    public function testAnOfferWithRealTimeLeftIsNotCutShort(): void
    {
        $offer = $this->offerRunningOutDuringTheRun();
        $offer->update(['expires_at' => now()->addDay()]);

        $this->artisan('assign:expire')->assertExitCode(0);

        self::assertSame(StatusEnum::NOTIFIED->value, $offer->refresh()->status);
    }

    /**
     * An offer sent by the run one cycle ago, so it runs out two seconds into this run.
     */
    private function offerRunningOutDuringTheRun(): Assignment
    {
        $video = Video::factory()->create(['processing_status' => ProcessingStatusEnum::Completed]);
        Clip::factory()->for($video, 'video')->create();

        return Assignment::factory()->forVideo($video)->forChannel($this->channel('Sent'))->create([
            'status' => StatusEnum::NOTIFIED->value,
            'expires_at' => now()->addSeconds(2),
        ]);
    }

    private function channel(string $name): Channel
    {
        return Channel::factory()->create(['name' => $name, 'weight' => 1, 'weekly_quota' => 10]);
    }
}
