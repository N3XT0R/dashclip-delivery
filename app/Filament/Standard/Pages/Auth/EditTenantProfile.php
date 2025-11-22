<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\Auth;

use Filament\Pages\Tenancy\EditTenantProfile as BaseProfile;

class EditTenantProfile extends BaseProfile
{
    public static function getLabel(): string
    {
        return 'Profile';
    }

}