<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enum\Users\RoleEnum;
use App\Models\User;
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
            $user->assignRole(RoleEnum::REGULAR->value);
        }
    }

    private function createOwnTeamForUser(User $user): void
    {
        app(TeamRepository::class)->createOwnTeamForUser($user);
    }
}