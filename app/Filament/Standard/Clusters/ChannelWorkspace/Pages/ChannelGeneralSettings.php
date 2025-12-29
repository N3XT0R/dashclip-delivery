<?php

namespace App\Filament\Standard\Clusters\ChannelWorkspace\Pages;

use App\Filament\Standard\Clusters\ChannelWorkspace\ChannelWorkspace;
use Filament\Pages\Page;

class ChannelGeneralSettings extends Page
{
    protected static ?string $cluster = ChannelWorkspace::class;
    protected string $view = 'filament.standard.pages.channel-general-settings';
}
