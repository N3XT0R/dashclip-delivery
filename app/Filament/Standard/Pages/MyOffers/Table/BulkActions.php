<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers\Table;

use App\Filament\Standard\Pages\MyOffers;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

final class BulkActions
{
    /**
     * @return array<int, BulkAction>
     */
    public function make(MyOffers $page): array
    {
        return [
            $this->downloadSelected($page),
            $this->returnSelected($page),
        ];
    }

    /* -----------------------------------------------------------------
     | Public bulk action factories
     | -----------------------------------------------------------------
     */

    public function downloadSelected(MyOffers $page): BulkAction
    {
        return BulkAction::make('download_selected')
            ->label(
                fn(Collection $records): string => __(
                    'my_offers.table.bulk_actions.download_selected'
                )
            )
            ->icon('heroicon-m-arrow-down-tray')
            ->color('primary')
            ->action(function (SupportCollection $records) use ($page): void {
                $page->dispatch('zip-download', [
                    'assignmentIds' => $records->pluck('id')->values()->all(),
                ]);
            })
            ->visible(
                fn(): bool => $page->activeTab === 'available'
            );
    }

    public function returnSelected(MyOffers $page): BulkAction
    {
        return BulkAction::make('return_selected')
            ->label(
                fn(Collection $records): string => __(
                    'my_offers.table.bulk_actions.return_selected',
                )
            )
            ->icon('heroicon-m-arrow-uturn-left')
            ->color('danger')
            ->action(
                fn(SupportCollection $records) => $this->handleReturnSelected($records)
            )
            ->successNotificationTitle(__('my_offers.table.bulk_actions.return_selected_notification'))
            ->requiresConfirmation()
            ->visible(
                fn(): bool => $page->activeTab === 'available'
            );
    }


    /* -----------------------------------------------------------------
     | Internal handlers
     | -----------------------------------------------------------------
     */

    private function handleDownloadSelected(SupportCollection $records): void
    {
        // TODO: Implement bulk download logic
    }

    private function handleReturnSelected(SupportCollection $records): void
    {
    }
}
