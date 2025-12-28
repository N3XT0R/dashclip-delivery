<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Channel\Application;

use App\Application\Channel\Application\ApproveChannelAccess;
use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\ChannelApplication;
use App\Repository\RoleRepository;
use App\Services\Channel\ChannelOperatorService;
use App\Services\NotificationService;
use Tests\DatabaseTestCase;

final class ApproveChannelAccessTest extends DatabaseTestCase
{
    public function testHandleApprovesChannelAndSendsNotification(): void
    {
        $channelApplication = ChannelApplication::factory()
            ->forExistingChannel()
            ->create();

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->once())
            ->method('notifyChannelAccessApproved')
            ->with($channelApplication);

        $channelOperatorService = $this->createMock(ChannelOperatorService::class);
        $channelOperatorService->expects($this->once())
            ->method('approveUserChannelAccess')
            ->with($channelApplication->user, $channelApplication->channel);

        $roleRepository = $this->createMock(RoleRepository::class);
        $roleRepository->expects($this->once())
            ->method('giveRoleToUser')
            ->with($channelApplication->user, RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD);

        $useCase = new ApproveChannelAccess($notificationService, $channelOperatorService, $roleRepository);
        $useCase->handle($channelApplication);
    }
}
