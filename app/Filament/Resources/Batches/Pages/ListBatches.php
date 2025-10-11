<?php

namespace App\Filament\Resources\Batches\Pages;

use App\Enum\BatchTypeEnum;
use App\Filament\Resources\Batches\BatchResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListBatches extends ListRecords
{
    protected static string $resource = BatchResource::class;

    public function getTabs(): array
    {
        $tabs = [];
        $types = [
            BatchTypeEnum::ASSIGN->value,
            BatchTypeEnum::NOTIFY->value,
            BatchTypeEnum::INGEST->value,
        ];

        foreach ($types as $type) {
            $tabs[$type] = Tab::make()->query(fn($query) => $query->where('type', $type));
        }

        return $tabs;
    }
}
