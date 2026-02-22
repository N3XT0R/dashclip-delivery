<?php

namespace App\Filament\Standard\Resources\VideoResource\Pages;

use App\Filament\Standard\Resources\VideoResource;
use App\Filament\Standard\Resources\VideoResource\Widgets\VideoStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVideos extends ListRecords
{
    protected static string $resource = VideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('filament.general.actions.upload')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VideoStatsOverview::class,
        ];
    }
}
