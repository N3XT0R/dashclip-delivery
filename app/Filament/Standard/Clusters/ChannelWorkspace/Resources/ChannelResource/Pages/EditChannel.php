<?php

namespace App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\Pages;

use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChannel extends EditRecord
{
    protected static string $resource = ChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
