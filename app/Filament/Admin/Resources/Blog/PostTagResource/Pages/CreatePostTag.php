<?php

namespace App\Filament\Admin\Resources\Blog\PostTagResource\Pages;

use App\Filament\Admin\Resources\Blog\PostTagResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePostTag extends CreateRecord
{
    protected static string $resource = PostTagResource::class;
}
