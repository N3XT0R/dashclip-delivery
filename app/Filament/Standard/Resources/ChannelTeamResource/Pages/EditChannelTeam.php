<?php

namespace App\Filament\Standard\Resources\ChannelTeamResource\Pages;

use App\Filament\Standard\Resources\ChannelTeamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChannelTeam extends EditRecord
{
    protected static string $resource = ChannelTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }
}
