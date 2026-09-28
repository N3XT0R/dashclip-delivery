<?php

declare(strict_types=1);

namespace Tests\Integration\Pipelines\Ingest;

use App\Enum\Team\TeamSettingEnum;
use App\Exceptions\Censor\VideoCensorException;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Pipelines\Ingest\Context\IngestContext;
use App\Pipelines\Ingest\Step\CensorVideoStep;
use App\Repository\TeamSettingRepository;
use App\Services\Censor\VideoCensorInterface;
use App\ValueObjects\CensorResult;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

/**
 * A video is only blurred when its team asked for it, and a video that asked for it is never
 * handed on unblurred.
 */
final class CensorVideoStepTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function testAVideoOfATeamThatDidNotAskIsLeftAlone(): void
    {
        $video = $this->video($this->team());

        $context = new IngestContext($video);

        self::assertFalse($this->step(new NeverCalledCensor())->isApplicable($context));
        self::assertNull($video->refresh()->source_path);
    }

    public function testAVideoOfATeamThatAskedIsBlurredAndKeepsItsOriginal(): void
    {
        $team = $this->team(wantsBlurring: true);
        $video = $this->video($team);
        $original = $video->path;

        $this->step(new FakeCensor())->handle(new IngestContext($video));

        $video->refresh();
        self::assertSame($original, $video->source_path);
        self::assertNotSame($original, $video->path);
        self::assertStringContainsString('_blurred', (string)$video->path);
        Storage::disk('local')->assertExists($video->source_path);
        Storage::disk('local')->assertExists($video->path);
    }

    public function testTheWishIsDecidedOnceAndKeptOnTheVideo(): void
    {
        $team = $this->team(wantsBlurring: true);
        $video = $this->video($team);

        $this->step(new FakeCensor())->isApplicable(new IngestContext($video));

        self::assertTrue($video->refresh()->censor_requested);
    }

    public function testAVideoAlreadyBlurredIsNotBlurredAgain(): void
    {
        $team = $this->team(wantsBlurring: true);
        $video = $this->video($team);
        $video->update(['source_path' => 'videos/original.mp4', 'censor_requested' => true]);

        self::assertFalse($this->step(new NeverCalledCensor())->isApplicable(new IngestContext($video)));
    }

    public function testAVideoIsNotHandedOnWhenTheBlurringFails(): void
    {
        $team = $this->team(wantsBlurring: true);
        $video = $this->video($team);

        $this->expectException(VideoCensorException::class);

        $this->step(new FailingCensor())->handle(new IngestContext($video));
    }

    public function testAVideoIsNotHandedOnWhenBlurringIsNotSetUp(): void
    {
        $team = $this->team(wantsBlurring: true);
        $video = $this->video($team);

        $this->expectException(VideoCensorException::class);

        $this->step(new UnavailableCensor())->handle(new IngestContext($video));
    }

    private function step(VideoCensorInterface $censor): CensorVideoStep
    {
        $this->app->instance(VideoCensorInterface::class, $censor);

        return $this->app->make(CensorVideoStep::class);
    }

    private function team(bool $wantsBlurring = false): Team
    {
        $team = Team::factory()->forUser(User::factory()->create())->create();
        if ($wantsBlurring) {
            app(TeamSettingRepository::class)->set($team, TeamSettingEnum::CENSOR_LICENSE_PLATES, true);
        }

        return $team;
    }

    private function video(Team $team): Video
    {
        $video = Video::factory()->for($team, 'team')->create([
            'disk' => 'local',
            'path' => 'videos/clip.mp4',
        ]);
        Storage::disk('local')->put($video->path, 'video-bytes');

        return $video;
    }
}

final class FakeCensor implements VideoCensorInterface
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function censor(string $sourcePath, string $targetPath): CensorResult
    {
        file_put_contents($targetPath, 'blurred-bytes');

        return new CensorResult($targetPath, framesLookedAt: 3, regionsBlurred: 5);
    }
}

final class FailingCensor implements VideoCensorInterface
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function censor(string $sourcePath, string $targetPath): CensorResult
    {
        throw new VideoCensorException('no luck');
    }
}

final class UnavailableCensor implements VideoCensorInterface
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

final class NeverCalledCensor implements VideoCensorInterface
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function censor(string $sourcePath, string $targetPath): CensorResult
    {
        throw new VideoCensorException('must not be called');
    }
}
