<?php

namespace App\Filament\Admin\Resources\Configs\Pages;

use App\Filament\Admin\Resources\Configs\ConfigResource;
use App\Models\Config\Category;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListConfigs extends ListRecords
{
    protected static string $resource = ConfigResource::class;

    public function getTabs(): array
    {
        $tabs = [];
        $categories = Category::query()->where('is_visible', 1)->get();
        foreach ($categories as $category) {
            $tabs[$category->getAttribute('name')] = Tab::make()->query(
                fn($query) => $query->where('config_category_id', $category->getKey())
            );
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
