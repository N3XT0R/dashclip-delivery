<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enum\Users\RoleEnum;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        if (!$user->hasAnyRole()) {
            $user->assignRole(RoleEnum::REGULAR->value);
        }
    }
}