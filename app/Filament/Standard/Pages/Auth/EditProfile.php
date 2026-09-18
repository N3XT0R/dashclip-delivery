<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\Auth;

use App\Filament\Admin\Pages\Auth\EditProfile as BaseEditProfile;
use App\Http\Middleware\SetDefaultTenant;
use BezhanSalleh\FilamentShield\Middleware\SyncShieldTenant;
use Filament\Facades\Filament;

/**
 * Render the profile inside the panel layout, scoped to the user's default team.
 */
class EditProfile extends BaseEditProfile
{
    /**
     * @var array<class-string>
     */
    protected static string|array $routeMiddleware = [
        SetDefaultTenant::class,
        SyncShieldTenant::class,
    ];

    /**
     * Fall back to the compact layout when the user has no team to build the navigation for.
     */
    public static function isSimple(): bool
    {
        $user = Filament::auth()->user();

        return $user === null || Filament::getUserDefaultTenant($user) === null;
    }
}
