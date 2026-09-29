<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Video;

use App\Application\Video\BlurVideoNowUseCase;
use App\Enum\ProcessingStatusEnum;
use App\Enum\StatusEnum;
use App\Enum\Video\DeliveredVersionEnum;
use App\Exceptions\Censor\VideoCensorException;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\Download;
use App\Models\User;
use App\Models\Video;
use App\Notifications\VideoVersionSwitchedNotification;
use App\Services\Censor\VideoCensorInterface;
use App\Services\PreviewService;
use App\ValueObjects\CensorResult;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\DatabaseTestCase;

/**
 * A submitter can ask for the plates to be blurred after the upload as well, and keeps the
 * original beside the blurred copy.
 */
final class BlurVideoNowUseCaseTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('local');

        $previews = Mockery::mock(PreviewService::class);
        $previews->shouldReceive('generatePreviewForClip')->andReturn('previews/new.mp4');
        $this->app->instance(PreviewService::class, $previews);
    }

    public function testTheBlurredCopyBecomesTheFileThatIsHandedOut(): void
    {
        $video = $this->readyVideo();
        $this->app->instance(VideoCensorInterface::class, new WorkingCensor());

        app(BlurVideoNowUseCase::class)->handle($video);

        $video->refresh();
        self::assertSame('videos/clip_blurred.mp4', $video->path);
        self::assertSame('videos/clip.mp4', $video->source_path);
        self::assertSame(DeliveredVersionEnum::BLURRED, $video->delivered_version);
        self::assertTrue((bool)$video->censor_requested);
    }

    public function testAChannelThatDownloadedItIsTold(): void
    {
        $video = $this->readyVideo();
        $this->app->instance(VideoCensorInterface::class, new WorkingCensor());
        $channel = Channel::factory()->create();
        $operator = User::factory()->create();
        $channel->channelUsers()->attach($operator->getKey(), ['is_user_verified' => true]);
        $assignment = Assignment::factory()->forVideo($video)->forChannel($channel)
            ->create(['status' => StatusEnum::PICKEDUP->value]);
        Download::factory()->forAssignment($assignment)->create();

        app(BlurVideoNowUseCase::class)->handle($video);

        Notification::assertSentTo($operator, VideoVersionSwitchedNotification::class);
    }

    public function testAVideoThatAlreadyCarriesBothVersionsIsRefused(): void
    {
        $video = $this->readyVideo();
        $video->update(['source_path' => 'videos/original.mp4']);
        $this->app->instance(VideoCensorInterface::class, new WorkingCensor());

        $this->expectException(VideoCensorException::class);

        app(BlurVideoNowUseCase::class)->handle($video->refresh());
    }

    public function testAVideoThatIsNotReadyIsRefused(): void
    {
        $video = $this->readyVideo();
        $video->update(['processing_status' => ProcessingStatusEnum::Pending]);
        $this->app->instance(VideoCensorInterface::class, new WorkingCensor());

        $this->expectException(VideoCensorException::class);

        app(BlurVideoNowUseCase::class)->handle($video->refresh());
    }

    public function testWithoutTheToolingNothingHappens(): void
    {
        $video = $this->readyVideo();
        $this->app->instance(VideoCensorInterface::class, new MissingCensor());

        $this->expectException(VideoCensorException::class);

        app(BlurVideoNowUseCase::class)->handle($video);
    }

    public function testTheRequestIsWrittenToTheActivityLog(): void
    {
        $video = $this->readyVideo();
        $this->app->instance(VideoCensorInterface::class, new WorkingCensor());

        app(BlurVideoNowUseCase::class)->handle($video, User::factory()->create());

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'videos',
            'description' => 'Number plates blurred on request',
            'subject_id' => $video->getKey(),
        ]);
    }

    private function readyVideo(): Video
    {
        $video = Video::factory()->create([
            'disk' => 'local',
            'path' => 'videos/clip.mp4',
            'processing_status' => ProcessingStatusEnum::Completed,
        ]);
        Clip::factory()->forVideo($video)->create();
        Storage::disk('local')->put($video->path, 'video-bytes');

        return $video;
    }
}

final class WorkingCensor implements VideoCensorInterface
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function censor(string $sourcePath, string $targetPath): CensorResult
    {
        file_put_contents($targetPath, 'blurred-bytes');

        return new CensorResult($targetPath, framesLookedAt: 10, regionsBlurred: 4);
    }
}

final class MissingCensor implements VideoCensorInterface
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function censor(string $sourcePath, string $targetPath): CensorResult
    {
        throw new VideoCensorException('not set up');
    }
}
