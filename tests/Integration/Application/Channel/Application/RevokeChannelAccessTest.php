<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Channel\Application;

use App\Application\Channel\Application\RevokeChannelAccess;
use App\Models\Channel;
use App\Models\User;
use App\Services\Channel\ChannelOperatorService;
use Tests\DatabaseTestCase;

final class RevokeChannelAccessTest extends DatabaseTestCase
{
    public function testHandleRevokesChannelAccess(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();

        $channelOperatorService = $this->createMock(ChannelOperatorService::class);
        $channelOperatorService->expects($this->once())
            ->method('revokeUserChannelAccess')
            ->with($user, $channel);

        $useCase = new RevokeChannelAccess($channelOperatorService);
        $useCase->handle($user, $channel);
    }
}
