<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\ChannelApprovalController;
use App\Models\Channel;
use Tests\DatabaseTestCase;

class ChannelApprovalControllerTest extends DatabaseTestCase
{
    protected ChannelApprovalController $approvalController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->approvalController = $this->app->make(ChannelApprovalController::class);
    }


    public function testItApprovesChannelWhenTokenIsValid(): void
    {
        $channel = Channel::factory()->create([
            'is_video_reception_paused' => true,
            'approved_at' => null,
        ]);

        $token = $channel->getApprovalToken();

        // Act
        $response = $this->get(route('channels.approve', [
            'channel' => $channel,
            'token' => $token,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('channels.approved');
        $response->assertViewHas('channel', $channel);

        $this->assertFalse($channel->fresh()->is_video_reception_paused);
        $this->assertNotNull($channel->fresh()->approved_at);
    }

    public function testItAbortsWith403WhenTokenIsInvalid(): void
    {
        // Arrange
        $channel = Channel::factory()->create([
            'is_video_reception_paused' => true,
            'approved_at' => null,
        ]);

        $invalidToken = 'invalid-token';

        // Act
        $response = $this->get(route('channels.approve', [
            'channel' => $channel,
            'token' => $invalidToken,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertSee('Ungültiger Bestätigungslink.');

        $this->assertTrue($channel->fresh()->is_video_reception_paused);
        $this->assertNull($channel->fresh()->approved_at);
    }

    public function testItRendersApprovedViewWithChannelData(): void
    {
        // Arrange
        $channel = Channel::factory()->create();
        $token = $channel->getApprovalToken();


        // Act
        $response = $this->get(route('channels.approve', [
            'channel' => $channel,
            'token' => $token,
        ]));
        // Assert
        $response->assertStatus(200);
        $response->assertSee($channel->name);
        $response->assertSee('Ihr Kanal wurde erfolgreich für den wöchentlichen Video-Versand aktiviert.');
    }
}