<?php

declare(strict_types=1);

namespace Tests\Integration\Commands;

use App\Constants\Config\DefaultConfigEntry;
use App\Facades\Cfg;
use App\Models\Video;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

final class PurgeDeletedVideosCommandTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Cfg::set(DefaultConfigEntry::POST_EXPIRY_RETENTION_WEEKS, 1, 'default', 'int');
    }

    public function testPurgesDeletedVideosAndReportsTheCount(): void
    {
        $video = $this->deletedVideo();

        $this->artisan('videos:purge-deleted')
            ->expectsOutputToContain('Removed 1 deleted video(s), 0 failed.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('videos', ['id' => $video->getKey()]);
    }

    public function testDryRunOnlyReports(): void
    {
        $video = $this->deletedVideo();

        $this->artisan('videos:purge-deleted', ['--dry-run' => true])
            ->expectsOutputToContain('Would remove 1 deleted video(s).')
            ->assertExitCode(0);

        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
    }

    public function testRunsDailyWithoutOverlapping(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event): bool => str_contains((string)$event->command, 'videos:purge-deleted'));

        self::assertNotNull($event, 'videos:purge-deleted is not scheduled');
        self::assertSame('0 4 * * *', $event->expression);
        self::assertTrue($event->withoutOverlapping);
    }

    private function deletedVideo(): Video
    {
        $video = Video::factory()->create(['disk' => 'local']);
        Storage::disk('local')->put($video->path, 'video-bytes');
        $video->delete();
        $video->forceFill(['deleted_at' => now()->subWeeks(2)])->saveQuietly();

        return $video;
    }
}
