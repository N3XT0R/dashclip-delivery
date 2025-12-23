<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers\Table;

use Filament\Actions\BulkAction;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

final class BulkActions
{
    /**
     * @return array<int, BulkAction>
     */
    public function make(Page $page): array
    {
        return [
            $this->downloadSelected($page),
        ];
    }

    /* -----------------------------------------------------------------
     | Public bulk action factories
     | -----------------------------------------------------------------
     */

    public function downloadSelected(Page $page): BulkAction
    {
        return BulkAction::make('download_selected')
            ->label(
                fn(Collection $records): string => __(
                    'my_offers.table.bulk_actions.download_selected',
                    ['count' => $records->count()]
                )
            )
            ->icon('heroicon-m-arrow-down-tray')
            ->color('primary')
            ->action(
                fn() => $this->handleDownloadSelected()
            )
            ->visible(
                fn(): bool => $page->activeTab === 'available'
            );
    }

    /* -----------------------------------------------------------------
     | Internal handlers
     | -----------------------------------------------------------------
     */

    private function handleDownloadSelected(): void
    {
        // TODO: Implement bulk download logic
    }
}
