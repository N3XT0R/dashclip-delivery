<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages;

use App\Filament\Standard\Pages\Traits\ChannelOwnerContextTrait;
use App\Filament\Standard\Pages\Traits\UserAccessChannelTrait;
use Filament\Pages\Page;

/**
 * Abstract page class for channel owner
 */
abstract class AbstractChannelOwnerPage extends Page
{
    use ChannelOwnerContextTrait;
    use UserAccessChannelTrait;

    public static function canAccess(): bool
    {
        return static::userCanAccessChannelPageStatic();
    }
}
