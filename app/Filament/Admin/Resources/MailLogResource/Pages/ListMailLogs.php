<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\MailLogResource\Pages;

use App\Filament\Admin\Resources\MailLogResource;
use Filament\Resources\Pages\ListRecords;

class ListMailLogs extends ListRecords
{
    protected static string $resource = MailLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
