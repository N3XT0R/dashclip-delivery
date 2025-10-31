<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\ChannelCreated;
use App\Models\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use PHPUnit\Framework\TestCase;

class ChannelCreatedTest extends TestCase
{
    public function testItStoresTheChannelInstance(): void
    {
        // Arrange
        $channel = new Channel();
        $channel->forceFill(['id' => 123, 'name' => 'Test Channel']);

        // Act
        $event = new ChannelCreated($channel);

        // Assert
        $this->assertSame($channel, $event->channel, 'The channel property should match the passed instance');
    }

    public function testItReturnsPrivateChannelWithChannelId(): void
    {
        // Arrange
        $channel = new Channel();
        $channel->forceFill(['id' => 42]);
        $event = new ChannelCreated($channel);

        // Act
        $broadcastChannels = $event->broadcastOn();

        // Assert
        $this->assertIsArray($broadcastChannels, 'broadcastOn should return an array');
        $this->assertCount(1, $broadcastChannels, 'Expected exactly one broadcast channel');
        $this->assertInstanceOf(PrivateChannel::class, $broadcastChannels[0]);

        // Expected format: "channel.{id}"
        $this->assertEquals('private-channel.42', $broadcastChannels[0]->name,
            'Channel name should include the channel ID');
    }

    public function testItReturnsEmptyStringIfChannelIdIsNull(): void
    {
        // Arrange
        $channel = new Channel(); // No ID set
        $event = new ChannelCreated($channel);

        // Act
        $broadcastChannels = $event->broadcastOn();

        // Assert
        $this->assertEquals('private-channel.', $broadcastChannels[0]->name,
            'If the channel has no ID, the suffix should be empty');
    }
}
