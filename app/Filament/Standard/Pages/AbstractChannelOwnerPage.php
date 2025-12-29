<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages;

use App\Filament\Standard\Pages\Traits\ChannelOwnerContextTrait;
use Filament\Facades\Filament;
use Filament\Pages\Page;

/**
 * Abstract page class for channel owner
 */
abstract class AbstractChannelOwnerPage extends Page
{
    use ChannelOwnerContextTrait;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user?->can('page.channels.access') ?? false;
    }
}
