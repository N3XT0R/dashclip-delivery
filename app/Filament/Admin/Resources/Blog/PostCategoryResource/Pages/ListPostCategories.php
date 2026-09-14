<?php

namespace App\Filament\Admin\Resources\Blog\PostCategoryResource\Pages;

use App\Filament\Admin\Resources\Blog\PostCategoryResource;
use App\Filament\Traits\HasBlogCreateActionTrait;
use Filament\Resources\Pages\ListRecords;

class ListPostCategories extends ListRecords
{
    use HasBlogCreateActionTrait;

    protected static string $resource = PostCategoryResource::class;

}
