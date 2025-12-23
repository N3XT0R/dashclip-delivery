<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers\Table;

use App\Filament\Standard\Pages\MyOffers;
use App\Models\Assignment;
use App\Services\AssignmentService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

final class Actions
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
        ];
    }

    /* -----------------------------------------------------------------
     | Public action factories
     | -----------------------------------------------------------------
     */

    public function viewDetails(Page $page): Action
    {
        return Action::make('view_details')
            ->label(__('my_offers.table.actions.view_details'))
            ->icon('heroicon-m-eye')
            ->modalHeading(__('my_offers.modal.title'))
            ->modalWidth(Width::FourExtraLarge)
            ->schema(
                fn(Assignment $record): Schema => $page->getDetailsInfolist($record)
            )
            ->modalSubmitAction(false)
            ->modalFooterActions([
                Action::make('return')
                    ->requiresConfirmation()
                    ->label(__('my_offers.table.actions.return_offer'))
                    ->color('danger')
                    ->visible(
                        fn(Assignment $record): bool => $this->assignmentService->canReturnAssignment($record)
                    )
                    ->action(
                        fn(Assignment $record) => $this->assignmentService->returnAssignment($record)
                    ),
            ])
            ->modalCancelActionLabel(__('common.close'));
    }

    public function downloadAgain(Page $page): Action
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

    public function download(Page $page): ViewAction
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

    public function returnOffer(Page $page): Action
    {
        return Action::make('return_offer')
            ->label(__('my_offers.table.actions.return_offer'))
            ->icon('heroicon-m-x-circle')
            ->color('danger')
            ->action(
                fn(Assignment $record) => $page->returnOffer($record)
            )
            ->visible(
                fn(Assignment $record): bool => $page->canReturnOffer($record)
            );
    }
}
