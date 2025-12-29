<?php

namespace App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\Pages;

use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewChannel extends ViewRecord
{
    protected static string $resource = ChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
