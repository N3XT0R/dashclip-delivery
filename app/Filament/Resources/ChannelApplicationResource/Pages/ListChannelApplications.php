<?php

namespace App\Filament\Resources\ChannelApplicationResource\Pages;

use App\Filament\Resources\ChannelApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListChannelApplications extends ListRecords
{
    protected static string $resource = ChannelApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    public function getTitle(): string
    {
        return __('filament.admin_channel_application.navigation_label');
    }
}
