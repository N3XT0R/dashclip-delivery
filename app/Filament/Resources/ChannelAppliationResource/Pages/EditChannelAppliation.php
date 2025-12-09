<?php

namespace App\Filament\Resources\ChannelAppliationResource\Pages;

use App\Filament\Resources\ChannelAppliationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChannelAppliation extends EditRecord
{
    protected static string $resource = ChannelAppliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
