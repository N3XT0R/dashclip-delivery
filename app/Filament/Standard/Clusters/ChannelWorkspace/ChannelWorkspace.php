<?php

namespace App\Filament\Standard\Clusters\ChannelWorkspace;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ChannelWorkspace extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'nav.channel_owner';


    protected static ?int $navigationSort = 100;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __(static::$navigationGroup);
    }

    public static function getNavigationLabel(): string
    {
        return __('channel-workspace.title');
    }
}
