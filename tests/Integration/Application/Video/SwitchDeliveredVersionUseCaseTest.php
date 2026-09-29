<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Video;

use App\Application\Video\SwitchDeliveredVersionUseCase;
use App\Enum\ProcessingStatusEnum;
use App\Enum\StatusEnum;
use App\Enum\Video\DeliveredVersionEnum;
use App\Exceptions\Video\VersionNotSwitchableException;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\Download;
use App\Models\User;
use App\Models\Video;
use App\Notifications\VideoVersionSwitchedNotification;
use App\Services\PreviewService;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\DatabaseTestCase;

/**
 * The submitter decides which of the two files of a video is handed out, also after the fact.
 */
final class SwitchDeliveredVersionUseCaseTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $preview = Mockery::mock(PreviewService::class);
        $preview->shouldReceive('generatePreviewForClip')->andReturn('previews/new.mp4');
        $this->app->instance(PreviewService::class, $preview);
    }

    public function testTheOtherFileIsHandedOutFromNowOn(): void
    {
        $video = $this->blurredVideo();

        $switched = app(SwitchDeliveredVersionUseCase::class)->handle($video);

        $video->refresh();
        self::assertSame(DeliveredVersionEnum::ORIGINAL, $switched);
        self::assertSame(DeliveredVersionEnum::ORIGINAL, $video->delivered_version);
        self::assertSame('videos/original.mp4', $video->path);
        self::assertSame('videos/blurred.mp4', $video->source_path);
        self::assertNotNull($video->version_switched_at);
    }

    public function testSwitchingBackHandsOutTheBlurredFileAgain(): void
    {
        $video = $this->blurredVideo();
        $useCase = app(SwitchDeliveredVersionUseCase::class);

        $useCase->handle($video);
        $useCase->handle($video->refresh());

        $video->refresh();
        self::assertSame(DeliveredVersionEnum::BLURRED, $video->delivered_version);
        self::assertSame('videos/blurred.mp4', $video->path);
    }

    public function testThePreviewsAreMadeAgainFromTheFileThatIsHandedOut(): void
    {
        $video = $this->blurredVideo();
        $clip = Clip::factory()->forVideo($video)->create(['preview_path' => 'previews/old.mp4']);

        app(SwitchDeliveredVersionUseCase::class)->handle($video);

        self::assertSame('previews/new.mp4', $clip->refresh()->preview_path);
    }

    public function testAChannelThatDownloadedItIsTold(): void
    {
        $video = $this->blurredVideo();
        $channel = Channel::factory()->create();
        $operator = User::factory()->create();
        $channel->channelUsers()->attach($operator->getKey(), ['is_user_verified' => true]);
        $assignment = Assignment::factory()->forVideo($video)->forChannel($channel)
            ->create(['status' => StatusEnum::PICKEDUP->value]);
        Download::factory()->forAssignment($assignment)->create();

        app(SwitchDeliveredVersionUseCase::class)->handle($video);

        Notification::assertSentTo($operator, VideoVersionSwitchedNotification::class);
    }

    public function testAChannelThatOnlyHoldsAnOfferIsNotTold(): void
    {
        $video = $this->blurredVideo();
        $channel = Channel::factory()->create();
        $operator = User::factory()->create();
        $channel->channelUsers()->attach($operator->getKey(), ['is_user_verified' => true]);
        Assignment::factory()->forVideo($video)->forChannel($channel)
            ->create(['status' => StatusEnum::NOTIFIED->value, 'expires_at' => now()->addWeek()]);

        app(SwitchDeliveredVersionUseCase::class)->handle($video);

        Notification::assertNothingSentTo($operator);
    }

    public function testAVideoWithOnlyOneVersionCannotSwitch(): void
    {
        $video = Video::factory()->create(['path' => 'videos/only.mp4']);

        $this->expectException(VersionNotSwitchableException::class);

        app(SwitchDeliveredVersionUseCase::class)->handle($video);
    }

    public function testAVideoWhoseFilesAreGoneCannotSwitch(): void
    {
        $video = $this->blurredVideo();
        $video->update(['processing_status' => ProcessingStatusEnum::Deleted]);

        $this->expectException(VersionNotSwitchableException::class);

        app(SwitchDeliveredVersionUseCase::class)->handle($video->refresh());
    }

    public function testTheSwitchIsWrittenToTheActivityLog(): void
    {
        $video = $this->blurredVideo();

        app(SwitchDeliveredVersionUseCase::class)->handle($video, User::factory()->create());

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'videos',
            'description' => 'Delivered video version switched',
            'subject_id' => $video->getKey(),
        ]);
    }

    private function blurredVideo(): Video
    {
        return Video::factory()->create([
            'path' => 'videos/blurred.mp4',
            'source_path' => 'videos/original.mp4',
            'delivered_version' => DeliveredVersionEnum::BLURRED->value,
            'processing_status' => ProcessingStatusEnum::Completed,
        ]);
    }
}
