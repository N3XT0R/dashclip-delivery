<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers\Table;

use App\Models\Assignment;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

final class Actions
{
    public function make(Page $page): array
    {
        return [
            Action::make('view_details')
                ->label(__('my_offers.table.actions.view_details'))
                ->icon('heroicon-m-eye')
                ->modalHeading(__('my_offers.modal.title'))
                ->modalWidth(Width::FourExtraLarge)
                ->schema(
                    fn(Assignment $record): Schema => $page->getDetailsInfolist($record)
                )
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Schließen'),

            // weitere Actions wie gehabt
        ];
    }
}
