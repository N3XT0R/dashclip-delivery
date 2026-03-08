<?php

namespace App\Filament\Admin\Resources\ChannelApplicationResource\Pages;

use App\Filament\Admin\Resources\ChannelApplicationResource;
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
