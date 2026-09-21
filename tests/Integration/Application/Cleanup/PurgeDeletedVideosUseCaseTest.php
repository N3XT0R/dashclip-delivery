<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Cleanup;

use App\Application\Cleanup\PurgeDeletedVideosUseCase;
use App\Constants\Config\DefaultConfigEntry;
use App\Facades\Cfg;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

final class PurgeDeletedVideosUseCaseTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Cfg::set(DefaultConfigEntry::POST_EXPIRY_RETENTION_WEEKS, 2, 'default', 'int');
    }

    public function testRemovesVideosDeletedLongerAgoThanTheRetentionPeriodWithTheirFile(): void
    {
        $video = $this->deletedVideo(weeksAgo: 3);

        $result = app(PurgeDeletedVideosUseCase::class)->handle();

        self::assertSame(1, $result->purged);
        self::assertSame(0, $result->failed);
        $this->assertDatabaseMissing('videos', ['id' => $video->getKey()]);
        Storage::disk('local')->assertMissing($video->path);
    }

    public function testKeepsVideosStillWithinTheRetentionPeriod(): void
    {
        $video = $this->deletedVideo(weeksAgo: 1);

        self::assertSame(0, app(PurgeDeletedVideosUseCase::class)->handle()->purged);

        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
        Storage::disk('local')->assertExists($video->path);
    }

    public function testNeverTouchesVideosThatAreNotDeleted(): void
    {
        $video = Video::factory()->create(['created_at' => now()->subYear()]);

        app(PurgeDeletedVideosUseCase::class)->handle();

        $this->assertDatabaseHas('videos', ['id' => $video->getKey(), 'deleted_at' => null]);
    }

    public function testFollowsTheConfiguredRetentionPeriod(): void
    {
        Cfg::set(DefaultConfigEntry::POST_EXPIRY_RETENTION_WEEKS, 5, 'default', 'int');
        $kept = $this->deletedVideo(weeksAgo: 4);
        $purged = $this->deletedVideo(weeksAgo: 6);

        app(PurgeDeletedVideosUseCase::class)->handle();

        $this->assertSoftDeleted('videos', ['id' => $kept->getKey()]);
        $this->assertDatabaseMissing('videos', ['id' => $purged->getKey()]);
    }

    public function testAVideoWhoseFileCannotBeRemovedStaysAndTheOthersAreStillPurged(): void
    {
        $stuck = $this->deletedVideo(weeksAgo: 3, disk: 'broken');
        $purged = $this->deletedVideo(weeksAgo: 3);

        $result = app(PurgeDeletedVideosUseCase::class)->handle();

        self::assertSame(1, $result->purged);
        self::assertSame(1, $result->failed);
        $this->assertSoftDeleted('videos', ['id' => $stuck->getKey()]);
        $this->assertDatabaseMissing('videos', ['id' => $purged->getKey()]);
    }

    public function testDryRunRemovesNothing(): void
    {
        $video = $this->deletedVideo(weeksAgo: 3);

        $result = app(PurgeDeletedVideosUseCase::class)->handle(dryRun: true);

        self::assertSame(1, $result->purged);
        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
        Storage::disk('local')->assertExists($video->path);
    }

    private function deletedVideo(int $weeksAgo, string $disk = 'local'): Video
    {
        $video = Video::factory()->create(['disk' => $disk]);
        if ($disk === 'local') {
            Storage::disk('local')->put($video->path, 'video-bytes');
        }
        $video->delete();
        $video->forceFill(['deleted_at' => now()->subWeeks($weeksAgo)])->saveQuietly();

        return $video;
    }
}
