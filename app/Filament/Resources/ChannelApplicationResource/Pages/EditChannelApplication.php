<?php

namespace App\Filament\Resources\ChannelApplicationResource\Pages;

use App\Filament\Resources\ChannelApplicationResource;
use Filament\Resources\Pages\EditRecord;

class EditChannelApplication extends EditRecord
{
    protected static string $resource = ChannelApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
