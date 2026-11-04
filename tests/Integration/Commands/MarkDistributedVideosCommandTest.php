<?php

declare(strict_types=1);

namespace Tests\Integration\Commands;

use App\Constants\Config\DefaultConfigEntry;
use App\Enum\StatusEnum;
use App\Facades\Cfg;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Video;
use Illuminate\Console\Scheduling\Schedule;
use Tests\DatabaseTestCase;

final class MarkDistributedVideosCommandTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Channel::query()->delete();
        Cfg::set(DefaultConfigEntry::DISTRIBUTION_ROUNDS, 1, 'default', 'int');
    }

    public function testMarksDistributedVideosAndReportsTheCount(): void
    {
        $video = $this->distributedVideo();

        $this->artisan('videos:mark-distributed')
            ->expectsOutputToContain('Marked 1 distributed video(s) as deleted.')
            ->assertExitCode(0);

        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
    }

    public function testDryRunOnlyReports(): void
    {
        $video = $this->distributedVideo();

        $this->artisan('videos:mark-distributed', ['--dry-run' => true])
            ->expectsOutputToContain('Would mark 1 distributed video(s) as deleted.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('videos', ['id' => $video->getKey(), 'deleted_at' => null]);
    }

    public function testRunsDailyAfterTheExpiryRun(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event): bool => str_contains((string)$event->command, 'videos:mark-distributed'));

        self::assertNotNull($event, 'videos:mark-distributed is not scheduled');
        self::assertSame('30 3 * * *', $event->expression);
    }

    private function distributedVideo(): Video
    {
        $video = Video::factory()->create();
        Assignment::factory()->forVideo($video)->forChannel(Channel::factory()->create())->create([
            'status' => StatusEnum::EXPIRED->value,
            'expires_at' => now()->subDay(),
        ]);

        return $video;
    }
}
