<?php

namespace App\Filament\Standard\Resources\ChannelTeamResource\Pages;

use App\Filament\Standard\Resources\ChannelTeamResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateChannelTeam extends CreateRecord
{
    protected static string $resource = ChannelTeamResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = Filament::getTenant()?->getKey();

        return $data;
    }
}
