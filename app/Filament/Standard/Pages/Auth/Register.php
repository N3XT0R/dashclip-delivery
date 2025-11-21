<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\Auth;

use App\Enum\Users\RoleEnum;
use App\Models\User;
use Filament\Auth\Pages\Register as BaseRegister;

class Register extends BaseRegister
{
    protected function handleRegistration(array $data): User
    {
        /**
         * @var User $user
         */
        $user = parent::handleRegistration($data);
        $user->syncRoles([RoleEnum::REGULAR->value]);

        return $user;
    }
}