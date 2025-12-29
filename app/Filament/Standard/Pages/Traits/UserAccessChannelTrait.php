<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\Traits;

trait UserAccessChannelTrait
{
    protected function userCanAccessChannelPage(): bool
    {
        return self::userCanAccessChannelPageStatic();
    }

    public static function userCanAccessChannelPageStatic(): bool
    {
        $user = auth()->user();

        return $user?->can('page.channels.access') ?? false;
    }
}
