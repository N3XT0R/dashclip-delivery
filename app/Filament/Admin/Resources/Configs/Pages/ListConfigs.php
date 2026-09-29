<?php

namespace App\Filament\Admin\Resources\Configs\Pages;

use App\Constants\Config\CensorConfigEntry;
use App\Filament\Admin\Resources\Configs\ConfigResource;
use App\Models\Config\Category;
use App\Services\Censor\CensorSettingsService;
use App\Services\Censor\VideoCensorInterface;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Support\Htmlable;

class ListConfigs extends ListRecords
{
    protected static string $resource = ConfigResource::class;

    public function getTabs(): array
    {
        $tabs = [];
        $categories = Category::query()->where('is_visible', 1)->get();
        foreach ($categories as $category) {
            $tabs[$category->getAttribute('name')] = Tab::make()->query(
                fn ($query) => $query->where('config_category_id', $category->getKey())
            );
        }

        return $tabs;
    }

    /**
     * Point out that the values of the blurring do nothing while the server lacks the tooling.
     */
    public function getSubheading(): string|Htmlable|null
    {
        if (!$this->isCensorTabActive() || app(VideoCensorInterface::class)->isAvailable()) {
            return null;
        }

        // an administrator can switch it off here, the tooling is a matter of the machine
        return __(app(CensorSettingsService::class)->isEnabled()
            ? 'configs.censor.not_installed'
            : 'configs.censor.switched_off');
    }

    private function isCensorTabActive(): bool
    {
        $censorCategory = Category::query()
            ->where('slug', CensorConfigEntry::CATEGORY)
            ->value('name');

        return $censorCategory !== null && $this->activeTab === $censorCategory;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
