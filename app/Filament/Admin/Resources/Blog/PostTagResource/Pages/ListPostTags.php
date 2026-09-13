<?php

namespace App\Filament\Admin\Resources\Blog\PostTagResource\Pages;

use App\Filament\Admin\Resources\Blog\PostTagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPostTags extends ListRecords
{
    protected static string $resource = PostTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
