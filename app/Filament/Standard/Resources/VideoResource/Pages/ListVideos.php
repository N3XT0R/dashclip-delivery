<?php

namespace App\Filament\Standard\Resources\VideoResource\Pages;

use App\Filament\Standard\Resources\VideoResource;
use App\Filament\Standard\Resources\VideoResource\Widgets\VideoStatsOverview;
use Filament\Resources\Pages\ListRecords;

class ListVideos extends ListRecords
{
    protected static string $resource = VideoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VideoStatsOverview::class,
        ];
    }
}
