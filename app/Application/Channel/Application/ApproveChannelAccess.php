<?php

declare(strict_types=1);

namespace App\Application\Channel\Application;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\ChannelApplication;
use App\Repository\RoleRepository;
use App\Services\Channel\ChannelOperatorService;
use App\Services\NotificationService;

final readonly class ApproveChannelAccess
{
    public function __construct(
        private NotificationService $notificationService,
        private ChannelOperatorService $channelOperatorService,
        private RoleRepository $roleRepository,
    ) {
    }

    public function handle(ChannelApplication $channelApplication): void
    {
        $role = RoleEnum::CHANNEL_OPERATOR;
        $guard = GuardEnum::STANDARD;

        $user = $channelApplication->user;
        if (!$this->roleRepository->hasRole($user, $role, $guard)) {
            $this->roleRepository->giveRoleToUser(
                $user,
                $role,
                $guard
            );
        }


        $this->channelOperatorService->approveUserChannelAccess(
            $channelApplication->user,
            $channelApplication->channel
        );
        $this->notificationService->notifyChannelAccessApproved($channelApplication);
    }
}
