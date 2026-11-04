<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Video;

use App\Application\Video\RestoreEarlyMarkedVideosUseCase;
use App\Enum\ProcessingStatusEnum;
use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\Video;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\DatabaseTestCase;

/**
 * Videos that were marked as deleted while one of their downloads was still running come back.
 */
final class RestoreEarlyMarkedVideosUseCaseTest extends DatabaseTestCase
{
    private RestoreEarlyMarkedVideosUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCase = app(RestoreEarlyMarkedVideosUseCase::class);
    }

    public function testRestoresAVideoMarkedWhileItsDownloadWasStillRunning(): void
    {
        $video = $this->markedVideo(markedAt: now(), expiresAt: now()->addDays(3));
        $clipId = $video->clipsWithTrashed()->first()?->getKey();

        self::assertSame(1, $this->useCase->handle()->restored);

        $this->assertDatabaseHas('videos', ['id' => $video->getKey(), 'deleted_at' => null]);
        $this->assertDatabaseHas('clips', ['id' => $clipId, 'deleted_at' => null]);
    }

    public function testKeepsAVideoThatWasMarkedAfterItsOfferRanOut(): void
    {
        $video = $this->markedVideo(markedAt: now(), expiresAt: now()->subDay());

        self::assertSame(0, $this->useCase->handle()->restored);

        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
    }

    public function testSkipsAVideoWhoseFilesAreAlreadyGone(): void
    {
        $video = $this->markedVideo(markedAt: now(), expiresAt: now()->addDays(3));
        DB::table('videos')->where('id', $video->getKey())
            ->update(['processing_status' => ProcessingStatusEnum::Deleted->value]);

        $result = $this->useCase->handle();

        self::assertSame(0, $result->restored);
        self::assertSame(1, $result->skipped);
        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
    }

    public function testIgnoresVideosMarkedBeforeTheGivenMoment(): void
    {
        $video = $this->markedVideo(markedAt: now()->subMonths(2), expiresAt: now()->addDays(3));

        self::assertSame(0, $this->useCase->handle(since: now()->subWeek())->restored);

        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
    }

    public function testDryRunCountsWithoutRestoring(): void
    {
        $video = $this->markedVideo(markedAt: now(), expiresAt: now()->addDays(3));

        self::assertSame(1, $this->useCase->handle(dryRun: true)->restored);

        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
    }

    /**
     * A video with a downloaded offer that was marked as deleted at the given moment.
     */
    private function markedVideo(CarbonInterface $markedAt, CarbonInterface $expiresAt): Video
    {
        $video = Video::factory()->create();
        Clip::factory()->for($video, 'video')->create();
        Assignment::factory()->forVideo($video)->forChannel(Channel::factory()->create())->create([
            'status' => StatusEnum::PICKEDUP->value,
            'expires_at' => $expiresAt,
        ]);

        $video->delete();
        $timestamp = Carbon::parse($markedAt)->toDateTimeString();
        DB::table('videos')->where('id', $video->getKey())->update(['deleted_at' => $timestamp]);
        DB::table('clips')->where('video_id', $video->getKey())->update(['deleted_at' => $timestamp]);

        return $video->refresh();
    }
}
