<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers\Table;

use App\Application\Channel\GetCurrentChannel;
use App\Enum\OfferExportFormatEnum;
use App\Filament\Standard\Exports\OfferExporter;
use App\Jobs\BuildOfferExportZipJob;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;

/**
 * Configures the offer export actions so bulk and row downloads build the same ZIP.
 */
final readonly class OfferExportActionConfigurator
{
    public function __construct(private GetCurrentChannel $currentChannel)
    {
    }

    public function configure(ExportAction|ExportBulkAction $action): ExportAction|ExportBulkAction
    {
        return $action
            ->exporter(OfferExporter::class)
            ->job(BuildOfferExportZipJob::class)
            ->formats([OfferExportFormatEnum::Zip])
            ->columnMapping(false)
            ->maxRows(500)
            ->modalSubmitActionLabel(__('my_offers.export.submit'))
            ->options(fn (): array => ['channel_id' => $this->currentChannel->handle()?->getKey()]);
    }
}
