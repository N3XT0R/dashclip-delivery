<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers\Table;

use App\Application\Offer\ReturnAssignment;
use App\Filament\Standard\Pages\MyOffers;
use Filament\Actions\BulkAction;
use Filament\Actions\ExportBulkAction;
use Illuminate\Support\Collection as SupportCollection;

final readonly class BulkActions
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

    public function downloadSelected(MyOffers $page): ExportBulkAction
    {
        return app(OfferExportActionConfigurator::class)->configure(ExportBulkAction::make('download_selected'))
            ->label(__('my_offers.table.bulk_actions.download_selected'))
            ->icon('heroicon-m-arrow-down-tray')
            ->color('primary')
            ->visible(fn (): bool => $page->activeTab === 'available');
    }

    public function returnSelected(MyOffers $page): BulkAction
    {
        return BulkAction::make('return_selected')
            ->label(__('my_offers.table.bulk_actions.return_selected'))
            ->icon('heroicon-m-arrow-uturn-left')
            ->color('danger')
            ->action(
                fn(SupportCollection $records) => app(ReturnAssignment::class)->handle($records)
            )
            ->successNotificationTitle(__('my_offers.table.bulk_actions.return_selected_notification'))
            ->requiresConfirmation()
            ->visible(
                fn(): bool => $page->activeTab === 'available'
            );
    }
}
