<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\Channel;
use App\Services\ChannelService;
use InvalidArgumentException;
use Tests\DatabaseTestCase;

class ChannelServiceTest extends DatabaseTestCase
{
    protected ChannelService $channelService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->channelService = $this->app->make(ChannelService::class);
    }

    public function testApproveUpdatesChannelWhenTokenIsValid(): void
    {
        // Arrange
        $channel = Channel::factory()->create([
            'is_video_reception_paused' => true,
            'approved_at' => null,
        ]);

        $token = $channel->getApprovalToken();

        // Act
        $this->channelService->approve($channel, $token);

        // Assert
        $updated = $channel->fresh();

        $this->assertFalse($updated->is_video_reception_paused);
        $this->assertNotNull($updated->approved_at);
    }

    public function testApproveThrowsExceptionWhenTokenIsInvalid(): void
    {
        // Arrange
        $channel = Channel::factory()->create([
            'is_video_reception_paused' => true,
            'approved_at' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültiger Bestätigungslink.');

        // Act
        $this->channelService->approve($channel, 'invalid-token');
    }
}