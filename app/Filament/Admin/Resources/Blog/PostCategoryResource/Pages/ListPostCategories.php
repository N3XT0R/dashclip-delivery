<?php

namespace App\Filament\Admin\Resources\Blog\PostCategoryResource\Pages;

use App\Filament\Admin\Resources\Blog\PostCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPostCategories extends ListRecords
{
    protected static string $resource = PostCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
