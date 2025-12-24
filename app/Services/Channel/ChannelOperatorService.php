<?php

declare(strict_types=1);

namespace App\Services\Channel;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Channel;
use App\Models\User;
use App\Repository\ChannelRepository;
use App\Repository\RoleRepository;
use Throwable;

readonly class ChannelOperatorService
{

    public function __construct(
        private RoleRepository $roleRepository,
        private ChannelRepository $channelRepository
    ) {
    }


    /**
     * Add user to channel and assign channel operator role
     * @param User $user
     * @param Channel $channel
     * @return void
     * @throws Throwable
     */
    public function addUserToChannel(User $user, Channel $channel): void
    {
        $guard = GuardEnum::STANDARD;
        $role = RoleEnum::CHANNEL_OPERATOR;

        $channelRepo = $this->channelRepository;
        $roleRepo = $this->roleRepository;

        try {
            if (!$channelRepo->hasUserAccessToChannel($user, $channel)) {
                $channelRepo->assignUserToChannel($user, $channel);
            }
            if (!$roleRepo->hasRole($user, $role, $guard)) {
                $roleRepo->giveRoleToUser($user, $role, $guard);
            }
        } catch (Throwable $e) {
            if ($channelRepo->hasUserAccessToChannel($user, $channel)) {
                $channelRepo->unassignUserFromChannel($user, $channel);
            }

            if (!$channelRepo->hasUserAccessToAnyChannel($user)) {
                $roleRepo->removeRoleFromUser($user, $role, $guard);
            }

            throw $e;
        }
    }
}
