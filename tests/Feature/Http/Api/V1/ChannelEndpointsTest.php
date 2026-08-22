<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use App\Models\Channel;
use App\Models\User;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class ChannelEndpointsTest extends DatabaseTestCase
{
    private function actingUserWithChannel(array $scopes): array
    {
        $user = User::factory()->withOwnTeam()->standard()->create();
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user->getKey(), ['is_user_verified' => true]);
        Passport::actingAs($user, $scopes);

        return [$user, $channel];
    }

    public function testIndexListsOnlyAccessibleChannels(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:read']);
        Channel::factory()->create();

        $this->getJson('/api/v1/channels')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $channel->getKey());
    }

    public function testShowHidesAdminFields(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:read']);

        $json = $this->getJson('/api/v1/channels/' . $channel->getKey())
            ->assertOk()->json('data');
        $this->assertArrayNotHasKey('weight', $json);
        $this->assertArrayNotHasKey('weekly_quota', $json);
        $this->assertArrayNotHasKey('approved_at', $json);
    }

    public function testUpdateTogglesVideoReceptionPause(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:write']);

        $this->patchJson('/api/v1/channels/' . $channel->getKey(), [
            'is_video_reception_paused' => true,
        ])->assertOk()->assertJsonPath('data.is_video_reception_paused', true);
    }

    public function testUpdateRejectsAdminFields(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:write']);

        $this->patchJson('/api/v1/channels/' . $channel->getKey(), ['weight' => 99])
            ->assertUnprocessable();
        $this->assertNotSame(99, $channel->refresh()->weight);
    }

    public function testForeignChannelReturns404(): void
    {
        $this->actingUserWithChannel(['channels:read', 'channels:write']);
        $foreign = Channel::factory()->create();

        $this->getJson('/api/v1/channels/' . $foreign->getKey())->assertNotFound();
    }

    public function testShowWithNonNumericIdReturns404(): void
    {
        $this->actingUserWithChannel(['channels:read']);

        $this->getJson('/api/v1/channels/abc')->assertNotFound();
    }
}
