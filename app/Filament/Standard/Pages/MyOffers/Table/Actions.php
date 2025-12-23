<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers\Table;

use App\Filament\Standard\Pages\MyOffers;
use App\Models\Assignment;
use App\Services\AssignmentService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

final readonly class Actions
{
    public function __construct(private AssignmentService $assignmentService)
    {
    }

    /**
     * @return array<int, Action>
     */
    public function make(MyOffers $page): array
    {
        return [
            $this->viewDetails($page),
            $this->downloadAgain($page),
            $this->download($page),
            $this->returnOffer($page),
        ];
    }

    /* -----------------------------------------------------------------
     | Public action factories
     | -----------------------------------------------------------------
     */

    public function viewDetails(MyOffers $page): Action
    {
        return Action::make('view_details')
            ->label(__('my_offers.table.actions.view_details'))
            ->icon('heroicon-m-eye')
            ->modalHeading(__('my_offers.modal.title'))
            ->modalWidth(Width::FourExtraLarge)
            ->schema(
                fn(?Assignment $record): ?Schema => $record !== null ? $page->getDetailsInfolist($record) : null
            )
            ->modalSubmitAction(false)
            ->modalFooterActions([
                $this->returnOffer($page),
            ])
            ->modalCancelActionLabel(__('common.close'));
    }

    public function downloadAgain(MyOffers $page): Action
    {
        return Action::make('download_again')
            ->label(__('my_offers.table.actions.download_again'))
            ->icon('heroicon-m-arrow-path')
            ->color('gray')
            ->url(
                fn(Assignment $record): string => '#'
            ) // TODO: Implement download URL
            ->openUrlInNewTab()
            ->visible(
                fn(): bool => $page->activeTab === 'downloaded'
            );
    }

    public function download(MyOffers $page): ViewAction
    {
        return ViewAction::make('download')
            ->label(__('my_offers.table.actions.download'))
            ->icon('heroicon-m-arrow-down-tray')
            ->color('primary')
            ->url(
                fn(Assignment $record): string => '#'
            ) // TODO: Implement download URL
            ->openUrlInNewTab()
            ->visible(
                fn(): bool => $page->activeTab === 'available'
            );
    }

    public function returnOffer(MyOffers $page): Action
    {
        return Action::make('return')
            ->requiresConfirmation()
            ->label(__('my_offers.table.actions.return_offer'))
            ->color('danger')
            ->visible(function (?Assignment $record) use ($page): bool {
                if ($record === null) {
                    return false;
                }

                return $page->activeTab === 'available' && $this->assignmentService->canReturnAssignment($record);
            })
            ->action(function (Assignment $record) use ($page) {
                $this->assignmentService->returnAssignment($record);
                $page->resetTable();
            });
    }
}
