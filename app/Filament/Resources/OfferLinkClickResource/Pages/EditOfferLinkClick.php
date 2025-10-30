<?php

namespace App\Filament\Resources\OfferLinkClickResource\Pages;

use App\Filament\Resources\OfferLinkClickResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfferLinkClick extends EditRecord
{
    protected static string $resource = OfferLinkClickResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
