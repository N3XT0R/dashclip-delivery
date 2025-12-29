<?php

declare(strict_types=1);

namespace App\Filament\Traits;

use App\Models\User;

trait UserAccessChannelTrait
{
    protected function userCanAccessChannelPage(?User $user = null): bool
    {
        return self::userCanAccessChannelPageStatic($user);
    }

    public static function userCanAccessChannelPageStatic(?User $user = null): bool
    {
        $user ??= auth()->user();
        return $user?->can('page.channels.access') ?? false;
    }
}
