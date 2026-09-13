<?php

namespace App\Filament\Admin\Resources\Blog\PostCategoryResource\Pages;

use App\Filament\Admin\Resources\Blog\PostCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePostCategory extends CreateRecord
{
    protected static string $resource = PostCategoryResource::class;
}
