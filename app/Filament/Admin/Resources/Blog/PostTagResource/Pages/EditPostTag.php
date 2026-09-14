<?php

namespace App\Filament\Admin\Resources\Blog\PostTagResource\Pages;

use App\Filament\Admin\Resources\Blog\PostTagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPostTag extends EditRecord
{
    protected static string $resource = PostTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
