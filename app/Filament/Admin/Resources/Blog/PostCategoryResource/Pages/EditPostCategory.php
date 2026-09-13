<?php

namespace App\Filament\Admin\Resources\Blog\PostCategoryResource\Pages;

use App\Filament\Admin\Resources\Blog\PostCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPostCategory extends EditRecord
{
    protected static string $resource = PostCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
