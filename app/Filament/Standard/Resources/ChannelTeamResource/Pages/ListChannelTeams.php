<?php

namespace App\Filament\Standard\Resources\ChannelTeamResource\Pages;

use App\Filament\Standard\Resources\ChannelTeamResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChannelTeams extends ListRecords
{
    protected static string $resource = ChannelTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
