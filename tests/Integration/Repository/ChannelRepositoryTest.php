<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Models\Channel;
use App\Repository\ChannelRepository;
use Tests\DatabaseTestCase;

class ChannelRepositoryTest extends DatabaseTestCase
{
    protected ChannelRepository $channelRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->channelRepository = $this->app->make(ChannelRepository::class);
    }

    public function testGetActiveChannelsReturnsOnlyUnpausedChannels(): void
    {
        // Ensure a clean database state
        Channel::query()->delete();

        // Arrange
        $activeA = Channel::factory()->create(['is_video_reception_paused' => false]);
        $activeB = Channel::factory()->create(['is_video_reception_paused' => false]);
        $paused = Channel::factory()->create(['is_video_reception_paused' => true]);

        // Act
        $channels = $this->channelRepository->getActiveChannels();

        // Assert
        $this->assertCount(2, $channels, 'Should return only unpaused channels');
        $this->assertTrue($channels->contains($activeA));
        $this->assertTrue($channels->contains($activeB));
        $this->assertFalse($channels->contains($paused));

        // Verify the ordering by ID
        $this->assertEquals(
            $channels->pluck('id')->sort()->values()->all(),
            $channels->pluck('id')->values()->all(),
            'Channels should be sorted by ID'
        );
    }

    public function testApproveUpdatesChannelCorrectly(): void
    {
        // Ensure a clean database state
        Channel::query()->delete();

        // Arrange
        $channel = Channel::factory()->create([
            'is_video_reception_paused' => true,
            'approved_at' => null,
        ]);

        // Act
        $result = $this->channelRepository->approve($channel);

        // Assert
        $this->assertTrue($result, 'Approve should return true on successful update');

        $updated = $channel->fresh();
        $this->assertFalse($updated->is_video_reception_paused);
        $this->assertNotNull($updated->approved_at);
        $this->assertDatabaseHas('channels', [
            'id' => $channel->id,
            'is_video_reception_paused' => false,
        ]);
    }

    public function testApproveReturnsFalseForNonPersistedChannel(): void
    {
        // Arrange: create a non-persisted model instance
        $channel = new Channel([
            'is_video_reception_paused' => true,
            'approved_at' => null,
        ]);

        // Act
        $result = $this->channelRepository->approve($channel);

        // Assert
        $this->assertFalse($result, 'Approve should return false when model is not persisted');
    }

    public function testGetActiveChannelsReturnsEmptyCollectionWhenAllPaused(): void
    {
        // Ensure a clean database state
        Channel::query()->delete();

        // Arrange
        Channel::factory()->count(3)->create(['is_video_reception_paused' => true]);

        // Act
        $channels = $this->channelRepository->getActiveChannels();

        // Assert
        $this->assertTrue($channels->isEmpty(), 'Should return an empty collection if all channels are paused');
    }
}
