<?php

namespace App\Filament\Standard\Clusters\ChannelWorkspace\Pages;

use App\Filament\Standard\Clusters\ChannelWorkspace\ChannelWorkspace;
use App\Filament\Standard\Pages\AbstractChannelOwnerPage;

class ChannelGeneralSettings extends AbstractChannelOwnerPage
{
    protected static ?string $cluster = ChannelWorkspace::class;
    protected string $view = 'filament.standard.pages.channel-general-settings';


    public static function getNavigationLabel(): string
    {
        return __('channel-workspace.channel_general_settings.title');
    }
}
