<?php

namespace App\Filament\Standard\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class ChannelApplication extends Page
{
    protected string $view = 'filament.standard.pages.channel-application';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencil;

    protected static ?string $title = 'filament.channel_application.title';
    protected static ?string $navigationLabel = 'filament.channel_application.navigation_label';
    protected static string|UnitEnum|null $navigationGroup = 'filament.channel_application.navigation_group';


    public function getTitle(): string|Htmlable
    {
        return __(static::$title);
    }

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public static function getNavigationGroup(): string
    {
        return __(static::$navigationGroup);
    }
}
