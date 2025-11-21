<?php

declare(strict_types=1);

namespace App\Enum\Users;

use App\Models\User;

enum RoleEnum: string
{
    case SUPER_ADMIN = 'super_admin';

    case REGULAR = 'panel_user';

    public static function canAccessEverything(User $user): bool
    {
        return $user->hasAllRoles([
            self::SUPER_ADMIN
        ]);
    }
}
