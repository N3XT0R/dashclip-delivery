<?php

namespace App\Filament\Admin\Resources\Blog\PostTagResource\Pages;

use App\Filament\Admin\Resources\Blog\PostTagResource;
use App\Filament\Traits\HasBlogCreateActionTrait;
use Filament\Resources\Pages\ListRecords;

class ListPostTags extends ListRecords
{
    use HasBlogCreateActionTrait;

    protected static string $resource = PostTagResource::class;

}
