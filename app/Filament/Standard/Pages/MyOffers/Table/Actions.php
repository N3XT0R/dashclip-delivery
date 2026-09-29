<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers\Table;

use App\Application\Assignment\UpdateAssignmentNote;
use App\Enum\ProcessingStatusEnum;
use App\Filament\Standard\Pages\MyOffers;
use App\Models\Assignment;
use App\Services\AssignmentService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
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
        return EditAction::make('view_details')
            ->label(__('my_offers.table.actions.view_details'))
            ->icon('heroicon-m-eye')
            ->modalHeading(__('my_offers.modal.title'))
            ->modalWidth(Width::FourExtraLarge)
            ->schema(
                fn (?Assignment $record): ?Schema => $record !== null ? $page->getDetailsInfolist($record) : null
            )
            ->modalFooterActions([
                $this->submit($page),
                $this->returnOffer($page),
            ])
            ->modalSubmitActionLabel(__('common.save'))
            ->modalCancelActionLabel(__('common.close'));
    }

    public function downloadAgain(MyOffers $page): Action
    {
        return Action::make('download_again')
            ->label(__('my_offers.table.actions.download_again'))
            ->icon('heroicon-m-arrow-path')
            ->color('gray')
            ->action(
                fn (Assignment $record) => $page->dispatchZipDownload([$record->getKey()])
            )
            ->openUrlInNewTab()
            ->visible(
                fn (?Assignment $record): bool => $page->activeTab === 'downloaded'
                    && $this->videoStillStoresItsFiles($record)
            );
    }

    /**
     * Whether the channel can still act on this offer: it is listed on a tab that allows it, its
     * video is there, and the offer itself may still be given back.
     * @param Assignment|null $record
     * @param MyOffers $page
     * @return bool
     */
    private function isStillOpenToTheChannel(?Assignment $record, MyOffers $page): bool
    {
        if ($record === null || $record->videoWithTrashed?->trashed()) {
            return false;
        }

        if (!in_array($page->activeTab, ['available', 'downloaded'], true)) {
            return false;
        }

        return $this->assignmentService->canReturnAssignment($record);
    }

    /**
     * Whether the video of an offer can still be delivered: a deleted video keeps its files until
     * the purge run removes them.
     * @param Assignment|null $record
     * @return bool
     */
    private function videoStillStoresItsFiles(?Assignment $record): bool
    {
        $video = $record?->videoWithTrashed;

        return $video !== null && $video->processing_status !== ProcessingStatusEnum::Deleted;
    }

    public function download(MyOffers $page): Action
    {
        return Action::make('download')
            ->label(__('my_offers.table.actions.download'))
            ->icon('heroicon-m-arrow-down-tray')
            ->color('primary')
            ->action(
                fn (Assignment $record) => $page->dispatchZipDownload([$record->getKey()])
            )
            ->openUrlInNewTab()
            ->visible(
                fn (?Assignment $record): bool => $page->activeTab === 'available'
                    && !($record?->videoWithTrashed?->trashed() ?? false)
            );
    }

    public function returnOffer(MyOffers $page): Action
    {
        return Action::make('return')
            ->requiresConfirmation()
            ->label(__('my_offers.table.actions.return_offer'))
            ->color('danger')
            ->visible(fn (?Assignment $record): bool => $this->isStillOpenToTheChannel($record, $page))
            ->action(function (Assignment $record) use ($page) {
                $this->assignmentService->returnAssignment($record, auth()->user());
                $page->resetTable();
            });
    }

    public function submit(MyOffers $page): Action
    {
        return Action::make('submit')
            ->label(__('common.save'))
            ->color('primary')
            ->visible(fn (?Assignment $record): bool => $this->isStillOpenToTheChannel($record, $page))
            ->action(function (Assignment $record) use ($page): void {
                app(UpdateAssignmentNote::class)->handle($record, $page->note, auth()->user());
                Notification::make()
                    ->title(__('my_offers.notifications.note_updated.title'))
                    ->success()
                    ->send();
            });
    }
}
