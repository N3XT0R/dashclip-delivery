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

    public function testChannelReceptionConfirmPageShowsWithoutConsumingToken(): void
    {
        $service = $this->app->make(\App\Services\ActionTokenService::class);
        $channel = \App\Models\Channel::factory()->create(['is_video_reception_paused' => true]);

        $plainToken = $service->issue(
            \App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION,
            subject: $channel,
            expiresAt: \Carbon\Carbon::now()->addMonth(),
        );

        $urlSegment = \App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value;

        // GET: should show confirm view WITHOUT consuming the token
        $this->get("/action-tokens/approve/{$urlSegment}/{$plainToken}")
            ->assertStatus(200)
            ->assertViewIs('tokens.channel-reception-confirm')
            ->assertViewHas('plainToken', $plainToken);

        // Token must still be valid (not consumed)
        $this->assertNotNull(
            $service->findValid(\App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION, $plainToken)
        );
    }

    public function testChannelReceptionPostConsumesTokenAndReactivatesChannel(): void
    {
        $service = $this->app->make(\App\Services\ActionTokenService::class);
        $channel = \App\Models\Channel::factory()->create(['is_video_reception_paused' => true]);

        $plainToken = $service->issue(
            \App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION,
            subject: $channel,
            expiresAt: \Carbon\Carbon::now()->addMonth(),
        );

        $urlSegment = \App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value;

        $this->post("/action-tokens/approve/{$urlSegment}/{$plainToken}")
            ->assertStatus(200)
            ->assertViewIs('tokens.channel-reception-reactivated');

        $this->assertFalse($channel->fresh()->is_video_reception_paused);

        // second POST → 410 (token consumed)
        $this->post("/action-tokens/approve/{$urlSegment}/{$plainToken}")
            ->assertStatus(410);
    }

    public function testChannelReceptionGetReturns410WhenTokenConsumed(): void
    {
        $service = $this->app->make(\App\Services\ActionTokenService::class);
        $channel = \App\Models\Channel::factory()->create(['is_video_reception_paused' => true]);

        $plainToken = $service->issue(
            \App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION,
            subject: $channel,
            expiresAt: \Carbon\Carbon::now()->addMonth(),
        );

        $urlSegment = \App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value;

        $service->consume(\App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION, $plainToken);

        $this->get("/action-tokens/approve/{$urlSegment}/{$plainToken}")
            ->assertStatus(410);
    }

    public function testPostForNonReactivationPurposeReturns404(): void
    {
        $this->post(
            '/action-tokens/approve/' .
            \App\Enum\TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL->value .
            '/some-token'
        )->assertStatus(404);
    }
}
