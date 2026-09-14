<?php

namespace App\Filament\Admin\Resources\Blog\PostResource\Pages;

use App\Filament\Admin\Resources\Blog\PostResource;
use App\Filament\Traits\HasBlogCreateActionTrait;
use Filament\Resources\Pages\ListRecords;

class ListPosts extends ListRecords
{
    use HasBlogCreateActionTrait;

    protected static string $resource = PostResource::class;

}
