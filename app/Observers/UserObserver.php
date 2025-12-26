<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\User;
use App\Repository\RoleRepository;
use App\Repository\TeamRepository;
use Illuminate\Database\Eloquent\Model;

class UserObserver extends BaseObserver
{
    public function created(User|Model $model): void
    {
        $this->createOwnTeamForUser($model);
        $this->assignDefaultRole($model);
    }


    private function assignDefaultRole(User $user): void
    {
        if (!$user->hasAnyRole()) {
            $repository = app(RoleRepository::class);
            $user->assignRole($repository->getRoleByRoleEnum(RoleEnum::REGULAR, GuardEnum::STANDARD->value));
        }
    }

    private function createOwnTeamForUser(User $user): void
    {
        $teamRepository = app(TeamRepository::class);
        $teamRepository->createOwnTeamForUser($user);
    }
}
