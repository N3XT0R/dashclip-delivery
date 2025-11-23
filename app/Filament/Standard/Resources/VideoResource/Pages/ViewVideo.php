<?php

namespace App\Filament\Standard\Resources\VideoResource\Pages;

use App\Filament\Standard\Resources\VideoResource;
use Filament\Resources\Pages\ViewRecord;

class ViewVideo extends ViewRecord
{
    protected static string $resource = VideoResource::class;


    protected function getHeaderActions(): array
    {
        return [];
    }

}
