<?php

namespace App\Filament\Admin\Resources\OfferLinkClickResource\Pages;

use App\Filament\Admin\Resources\OfferLinkClickResource;
use Filament\Resources\Pages\ListRecords;

class ListOfferLinkClicks extends ListRecords
{
    protected static string $resource = OfferLinkClickResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
