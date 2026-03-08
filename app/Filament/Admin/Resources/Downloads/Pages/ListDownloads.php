<?php

namespace App\Filament\Admin\Resources\Downloads\Pages;

use App\Filament\Admin\Resources\Downloads\DownloadResource;
use Filament\Resources\Pages\ListRecords;

class ListDownloads extends ListRecords
{
    protected static string $resource = DownloadResource::class;
}
