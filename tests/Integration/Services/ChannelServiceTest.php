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

    public function testPrepareChannelsAndPoolBuildsExpectedStructures(): void
    {
        // Clean slate
        Channel::query()->delete();

        // Arrange
        $channelA = Channel::factory()->create([
            'weight' => 2,
            'weekly_quota' => 3,
            'is_video_reception_paused' => false,
        ]);

        $channelB = Channel::factory()->create([
            'weight' => 1,
            'weekly_quota' => 5,
            'is_video_reception_paused' => false,
        ]);

        // Act
        [$channels, $rotationPool, $quota] = $this->channelService->prepareChannelsAndPool(null);

        // Assert
        $this->assertCount(2, $channels);
        $this->assertCount(3, $rotationPool);
        $this->assertEqualsCanonicalizing([$channelA->id, $channelB->id], $channels->pluck('id')->all());
        $this->assertEquals([$channelA->id => 3, $channelB->id => 5], $quota);
    }

    public function testPrepareChannelsAndPoolAppliesQuotaOverride(): void
    {
        // Clean slate
        Channel::query()->delete();

        // Arrange
        $channelA = Channel::factory()->create([
            'weight' => 2,
            'weekly_quota' => 3,
            'is_video_reception_paused' => false,
        ]);

        $channelB = Channel::factory()->create([
            'weight' => 1,
            'weekly_quota' => 5,
            'is_video_reception_paused' => false,
        ]);

        $quotaOverride = 42;

        // Act
        [$channels, $rotationPool, $quota] = $this->channelService->prepareChannelsAndPool($quotaOverride);

        // Assert
        $this->assertCount(2, $channels);
        $this->assertCount(3, $rotationPool);
        
        $this->assertEquals([
            $channelA->id => $quotaOverride,
            $channelB->id => $quotaOverride,
        ], $quota);
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