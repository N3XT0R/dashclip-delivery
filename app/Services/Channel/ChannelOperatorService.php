<?php

declare(strict_types=1);

namespace App\Services\Channel;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Channel;
use App\Models\User;
use App\Repository\ChannelRepository;
use App\Repository\RoleRepository;

class ChannelOperatorService
{

    public function __construct(
        private RoleRepository $roleRepository,
        private ChannelRepository $channelRepository
    ) {
    }


    public function addUserToChannel(User $user, Channel $channel): void
    {
        try {
            if (!$this->channelRepository->hasUserAccessToChannel($user, $channel)) {
                $this->channelRepository->assignUserToChannel($user, $channel);
            }
            if ($this->roleRepository->hasRole($user, RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD)) {
            }
        } catch (\Throwable $e) {
        }
    }
}