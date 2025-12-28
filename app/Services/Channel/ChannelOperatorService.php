<?php

declare(strict_types=1);

namespace App\Services\Channel;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Channel;
use App\Models\User;
use App\Repository\ChannelRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
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
     * @param bool $isUserVerified
     * @return bool
     * @throws Throwable
     */
    public function addUserToChannel(User $user, Channel $channel, bool $isUserVerified = false): bool
    {
        $guard = GuardEnum::STANDARD;
        $role = RoleEnum::CHANNEL_OPERATOR;

        $channelRepo = $this->channelRepository;
        $roleRepo = $this->roleRepository;

        try {
            if (!$channelRepo->hasUserAccessToChannel($user, $channel)) {
                $channelRepo->assignUserToChannel($user, $channel, $isUserVerified);
            }
            if (!$roleRepo->hasRole($user, $role, $guard)) {
                $roleRepo->giveRoleToUser($user, $role, $guard);
            }

            return true;
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

    /**
     * Approve user access to channel
     * @param User $user
     * @param Channel $channel
     * @return void
     */
    public function approveUserChannelAccess(User $user, Channel $channel): void
    {
        $channelRepo = $this->channelRepository;
        if (!$channelRepo->hasUserAccessToChannel($user, $channel)) {
            throw new \DomainException('User does not have access to the channel.');
        }

        $channelRepo->setUserVerifiedForChannel($user, $channel);
    }

    /**
     * Revoke user access to channel and remove channel operator role if no channels left
     * @param User $user
     * @param Channel $channel
     * @return void
     */
    public function revokeUserChannelAccess(User $user, Channel $channel): void
    {
        $channelRepo = $this->channelRepository;
        $roleRepo = $this->roleRepository;
        $guard = GuardEnum::STANDARD;
        $role = RoleEnum::CHANNEL_OPERATOR;

        if ($channelRepo->hasUserAccessToChannel($user, $channel)) {
            $channelRepo->unassignUserFromChannel($user, $channel);
        }

        if (!$channelRepo->hasUserAccessToAnyChannel($user)) {
            $roleRepo->removeRoleFromUser($user, $role, $guard);
        }
    }

    /**
     * Check if user is channel operator
     * @param User $user
     * @param Channel $channel
     * @return bool
     */
    public function isUserChannelOperator(User $user, Channel $channel): bool
    {
        $channelRepo = $this->channelRepository;
        $roleRepo = $this->roleRepository;
        $guard = GuardEnum::STANDARD;
        $role = RoleEnum::CHANNEL_OPERATOR;

        return $channelRepo->isUserVerifiedForChannel($user, $channel)
            && $roleRepo->hasRole($user, $role, $guard);
    }

    /**
     * Check if user by email is channel operator
     * @param Channel $channel
     * @return bool
     */
    public function isChannelEmailOwnerChannelOperator(Channel $channel): bool
    {
        $owner = $channel->email;

        if ($owner === null) {
            return false;
        }

        $user = app(UserRepository::class)->getUserByEmail($owner);
        if ($user === null) {
            return false;
        }

        return $this->isUserChannelOperator($user, $channel);
    }
}
