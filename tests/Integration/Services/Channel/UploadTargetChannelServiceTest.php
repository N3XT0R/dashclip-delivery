<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Channel;

use App\Models\Channel;
use App\Models\Team;
use App\Models\User;
use App\Services\Channel\UploadTargetChannelService;
use Tests\DatabaseTestCase;

final class UploadTargetChannelServiceTest extends DatabaseTestCase
{
    private UploadTargetChannelService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(UploadTargetChannelService::class);
    }

    public function testSelectableChannelsContainChannelsThatAcceptVideosWhenTeamHasNoneAssigned(): void
    {
        $channel = Channel::factory()->create();
        $team = $this->createTeam();

        $ids = $this->service->getSelectableChannels($team)->map(fn (Channel $c) => (int)$c->getKey())->all();

        self::assertContains((int)$channel->getKey(), $ids);
    }

    public function testSelectableChannelsExcludePausedChannels(): void
    {
        $paused = Channel::factory()->paused()->create();

        $ids = $this->service->getSelectableChannels(null)->map(fn (Channel $c) => (int)$c->getKey())->all();

        self::assertNotContains((int)$paused->getKey(), $ids);
    }

    public function testSelectableChannelsAreLimitedToTheChannelsAssignedToTheTeam(): void
    {
        $assigned = Channel::factory()->create();
        $other = Channel::factory()->create();
        $team = $this->createTeam();
        $team->assignedChannels()->attach($assigned->getKey(), ['quota' => 5]);

        $ids = $this->service->getSelectableChannels($team)->map(fn (Channel $c) => (int)$c->getKey())->all();

        self::assertSame([(int)$assigned->getKey()], $ids);
        self::assertNotContains((int)$other->getKey(), $ids);
    }

    public function testSelectableChannelsStayEmptyWhenEveryAssignedChannelIsPaused(): void
    {
        Channel::factory()->create();
        $paused = Channel::factory()->paused()->create();
        $team = $this->createTeam();
        $team->channelAssignments()->create([
            'channel_id' => $paused->getKey(),
            'quota' => 5,
        ]);

        self::assertTrue($this->service->getSelectableChannels($team)->isEmpty());
    }

    public function testSelectableChannelsAreSortedByName(): void
    {
        Channel::query()->delete();
        Channel::factory()->create(['name' => 'Zebra Clips']);
        Channel::factory()->create(['name' => 'Alpha Clips']);

        $names = $this->service->getSelectableChannels(null)->map(fn (Channel $c) => $c->name)->all();

        self::assertSame(['Alpha Clips', 'Zebra Clips'], $names);
    }

    public function testResolveSelectionReturnsTheSelectableChannel(): void
    {
        $channel = Channel::factory()->create();

        $resolved = $this->service->resolveSelection(null, (string)$channel->getKey());

        self::assertInstanceOf(Channel::class, $resolved);
        self::assertSame((int)$channel->getKey(), (int)$resolved->getKey());
    }

    public function testResolveSelectionReturnsNullForAChannelOutsideTheTeamScope(): void
    {
        $assigned = Channel::factory()->create();
        $foreign = Channel::factory()->create();
        $team = $this->createTeam();
        $team->assignedChannels()->attach($assigned->getKey(), ['quota' => 5]);

        self::assertNull($this->service->resolveSelection($team, (int)$foreign->getKey()));
    }

    public function testResolveSelectionReturnsNullForAPausedChannel(): void
    {
        $paused = Channel::factory()->paused()->create();

        self::assertNull($this->service->resolveSelection(null, (int)$paused->getKey()));
    }

    public function testResolveSelectionReturnsNullWithoutASelection(): void
    {
        self::assertNull($this->service->resolveSelection(null, null));
        self::assertNull($this->service->resolveSelection(null, ''));
    }

    private function createTeam(): Team
    {
        $user = User::factory()->create();

        return Team::factory()->forUser($user)->create();
    }
}
