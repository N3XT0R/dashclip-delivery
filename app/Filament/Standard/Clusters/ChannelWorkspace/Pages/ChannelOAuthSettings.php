<?php

namespace App\Filament\Standard\Clusters\ChannelWorkspace\Pages;

use App\Filament\Standard\Clusters\ChannelWorkspace\ChannelWorkspace;
use App\Filament\Standard\Pages\AbstractChannelOwnerPage;

class ChannelOAuthSettings extends AbstractChannelOwnerPage
{
    protected static ?string $cluster = ChannelWorkspace::class;
    protected string $view = 'filament.standard.pages.channel-o-auth-settings';


    public static function canAccess(): bool
    {
        return false;
    }
}
