<?php

declare(strict_types=1);

namespace App\Auth\Abilities;

use App\Enum\Users\RoleEnum;
use App\Models\User;
use App\Repository\ChannelRepository;
use App\Repository\RoleRepository;

final readonly class AccessChannelPageAbility
{
    public function __construct(
        private RoleRepository $roles,
        private ChannelRepository $channels,
    ) {
    }

    public function check(User $user): bool
    {
        return
            $this->roles->hasRole($user, RoleEnum::CHANNEL_OPERATOR)
            && $this->channels->hasUserAccessToAnyChannel($user);
    }
}
