<?php

namespace App\Filament\Resources\ChannelAppliationResource\Pages;

use App\Filament\Resources\ChannelAppliationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChannelAppliations extends ListRecords
{
    protected static string $resource = ChannelAppliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
