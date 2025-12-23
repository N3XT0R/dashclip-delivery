<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers\Table;

use Filament\Actions\BulkAction;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

final class BulkActions
{
    public function make(Page $page): array
    {
        return [
            BulkAction::make('download_selected')
                ->label(
                    fn(Collection $records): string => __('my_offers.table.bulk_actions.download_selected', [
                        'count' => $records->count(),
                    ])
                )
                ->icon('heroicon-m-arrow-down-tray')
                ->color('primary')
                ->action(fn() => null)
                ->visible(fn(): bool => $page->activeTab === 'available'),
        ];
    }
}
