<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enum\TokenPurposeEnum;
use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Models\User;
use App\Services\ActionTokenService;
use Symfony\Component\HttpFoundation\Response;
use Tests\DatabaseTestCase;

final class TokenApprovalControllerTest extends DatabaseTestCase
{
    public function testChannelAccessApprovedPageWorks(): void
    {
        $service = $this->app->make(ActionTokenService::class);

        $channel = Channel::factory()->create();
        $user = User::factory()->create();
        $channel->channelUsers()->attach($user->getKey());
        $application = ChannelApplication::factory()->create([
            'channel_id' => $channel->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $plainToken = $service->issue(
            TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL,
            subject: $application
        );

        $response = $this->get(
            '/action-tokens/approve/' .
            TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL->value .
            '/' . $plainToken
        );

        $response
            ->assertStatus(Response::HTTP_OK)
            ->assertViewIs('tokens.channel-access-approved')
            ->assertViewHas('token')
            ->assertViewHas('purpose', TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL);

        // zweiter Call → Token verbraucht
        $this->get(
            '/action-tokens/approve/' .
            TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL->value .
            '/' . $plainToken
        )->assertStatus(Response::HTTP_GONE);
    }

    public function testChannelActivationApprovedPageWorks(): void
    {
        $service = $this->app->make(ActionTokenService::class);

        $channel = Channel::factory()->create();

        $plainToken = $service->issue(
            TokenPurposeEnum::CHANNEL_ACTIVATION_APPROVAL,
            subject: $channel
        );

        $response = $this->get(
            '/action-tokens/approve/' .
            TokenPurposeEnum::CHANNEL_ACTIVATION_APPROVAL->value .
            '/' . $plainToken
        );

        $response
            ->assertStatus(Response::HTTP_OK)
            ->assertViewIs('tokens.channel-activation-approved')
            ->assertViewHas('token')
            ->assertViewHas('purpose', TokenPurposeEnum::CHANNEL_ACTIVATION_APPROVAL);

        // Token darf nur einmal funktionieren
        $this->get(
            '/action-tokens/approve/' .
            TokenPurposeEnum::CHANNEL_ACTIVATION_APPROVAL->value .
            '/' . $plainToken
        )->assertStatus(Response::HTTP_GONE);
    }

    public function testInvalidPurposeReturns404(): void
    {
        $this->get('/action-tokens/approve/invalid-purpose/foo')
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testUnknownTokenReturns410(): void
    {
        $this->get(
            '/action-tokens/approve/' .
            TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL->value .
            '/does-not-exist'
        )->assertStatus(Response::HTTP_GONE);
    }
}
