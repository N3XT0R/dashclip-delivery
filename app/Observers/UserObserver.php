<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enum\Users\RoleEnum;
use App\Models\User;
use App\Repository\TeamRepository;

class UserObserver
{
    public function created(User $user): void
    {
        $this->createOwnTeamForUser($user);
        $this->assignDefaultRole($user);
    }


    private function assignDefaultRole(User $user): void
    {
        if (!$user->hasAnyRole()) {
            $user->assignRole(RoleEnum::REGULAR->value);
        }
    }

    private function createOwnTeamForUser(User $user): void
    {
        app(TeamRepository::class)->createOwnTeamForUser($user);
    }
}