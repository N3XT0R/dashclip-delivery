<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Video;

use App\Application\Video\MarkDistributedVideosDeletedUseCase;
use App\Constants\Config\DefaultConfigEntry;
use App\Enum\StatusEnum;
use App\Facades\Cfg;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\ChannelVideoBlock;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use Tests\DatabaseTestCase;

/**
 * A video is marked as deleted once the distribution will never offer it again.
 */
final class MarkDistributedVideosDeletedUseCaseTest extends DatabaseTestCase
{
    private MarkDistributedVideosDeletedUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        Channel::query()->delete();
        Cfg::set(DefaultConfigEntry::DISTRIBUTION_ROUNDS, 1, 'default', 'int');
        $this->useCase = app(MarkDistributedVideosDeletedUseCase::class);
    }

    public function testMarksAVideoEveryChannelHasSeenWithoutARoundLeft(): void
    {
        $video = Video::factory()->create();
        $this->expiredOffer($video, $this->channel());

        self::assertSame(1, $this->useCase->handle()->marked);

        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
    }

    public function testMarksAVideoThatWasDownloadedEvenWhenChannelsAreLeft(): void
    {
        $video = Video::factory()->create();
        Assignment::factory()->forVideo($video)->forChannel($this->channel())
            ->create(['status' => StatusEnum::PICKEDUP->value]);
        $this->channel();

        self::assertSame(1, $this->useCase->handle()->marked);

        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
    }

    public function testKeepsAVideoWithAnOpenOffer(): void
    {
        $video = Video::factory()->create();
        Assignment::factory()->forVideo($video)->forChannel($this->channel())
            ->create(['status' => StatusEnum::NOTIFIED->value, 'expires_at' => now()->addDay()]);

        self::assertSame(0, $this->useCase->handle()->marked);

        $this->assertDatabaseHas('videos', ['id' => $video->getKey(), 'deleted_at' => null]);
    }

    public function testKeepsAVideoWithAChannelThatNeverHadIt(): void
    {
        $video = Video::factory()->create();
        $this->expiredOffer($video, $this->channel());
        $this->channel();

        self::assertSame(0, $this->useCase->handle()->marked);

        $this->assertDatabaseHas('videos', ['id' => $video->getKey(), 'deleted_at' => null]);
    }

    public function testKeepsAVideoWithAFreeRoundLeft(): void
    {
        Cfg::set(DefaultConfigEntry::DISTRIBUTION_ROUNDS, 2, 'default', 'int');
        $video = Video::factory()->create();
        $this->expiredOffer($video, $this->channel());

        self::assertSame(0, $this->useCase->handle()->marked);

        $this->assertDatabaseHas('videos', ['id' => $video->getKey(), 'deleted_at' => null]);
    }

    public function testKeepsAVideoWhileAChannelBlocksIt(): void
    {
        $video = Video::factory()->create();
        $blocking = $this->channel();
        ChannelVideoBlock::query()->create([
            'video_id' => $video->getKey(),
            'channel_id' => $blocking->getKey(),
            'until' => now()->addWeek(),
        ]);
        $this->expiredOffer($video, $this->channel());

        self::assertSame(0, $this->useCase->handle()->marked);

        $this->assertDatabaseHas('videos', ['id' => $video->getKey(), 'deleted_at' => null]);
    }

    public function testATeamVideoOnlyNeedsTheChannelsOfItsTeam(): void
    {
        $assigned = $this->channel();
        $this->channel();
        $team = Team::factory()->forUser(User::factory()->create())->create();
        $team->channelAssignments()->create(['channel_id' => $assigned->getKey(), 'quota' => 5]);
        $video = Video::factory()->for($team, 'team')->create();
        $this->expiredOffer($video, $assigned);

        self::assertSame(1, $this->useCase->handle()->marked);

        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
    }

    public function testNeverTouchesVideosWithoutAnyOffer(): void
    {
        $video = Video::factory()->create();
        $this->channel();

        self::assertSame(0, $this->useCase->handle()->marked);

        $this->assertDatabaseHas('videos', ['id' => $video->getKey(), 'deleted_at' => null]);
    }

    public function testDryRunCountsWithoutMarking(): void
    {
        $video = Video::factory()->create();
        $this->expiredOffer($video, $this->channel());

        self::assertSame(1, $this->useCase->handle(dryRun: true)->marked);

        $this->assertDatabaseHas('videos', ['id' => $video->getKey(), 'deleted_at' => null]);
    }

    private function channel(): Channel
    {
        return Channel::factory()->create(['weekly_quota' => 10]);
    }

    private function expiredOffer(Video $video, Channel $channel): Assignment
    {
        return Assignment::factory()->forVideo($video)->forChannel($channel)->create([
            'status' => StatusEnum::EXPIRED->value,
            'expires_at' => now()->subDay(),
        ]);
    }
}
