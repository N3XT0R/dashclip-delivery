<?php

declare(strict_types=1);

namespace App\Enum\Users;

enum RoleEnum: string
{
    case SUPER_ADMIN = 'super_admin';

    case REGULAR = 'panel_user';

    public static function getRoles(): array
    {
        return [
            self::SUPER_ADMIN,
            self::REGULAR,
        ];
    }
}
