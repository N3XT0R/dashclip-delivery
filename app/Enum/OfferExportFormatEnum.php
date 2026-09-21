<?php

declare(strict_types=1);

namespace App\Enum;

use App\Filament\Standard\Exports\OfferExportZipDownloader;
use Filament\Actions\Action;
use Filament\Actions\Exports\Downloaders\Contracts\Downloader;
use Filament\Actions\Exports\Enums\Contracts\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\URL;

/**
 * Offer exports are delivered as one ZIP with the videos and their info.csv.
 */
enum OfferExportFormatEnum: string implements ExportFormat
{
    case Zip = 'zip';

    public function getDownloader(): Downloader
    {
        return app(OfferExportZipDownloader::class);
    }

    public function getDownloadNotificationAction(Export $export, string $authGuard): Action
    {
        return Action::make('download_zip')
            ->label(__('my_offers.export.download'))
            ->url(self::downloadPath($export, $authGuard), shouldOpenInNewTab: true)
            ->markAsRead();
    }

    /**
     * Signed, host-independent path to the prepared ZIP of an offer export.
     * @param Export $export
     * @param string $authGuard guard the export's creator signs in with
     * @return string
     */
    public static function downloadPath(Export $export, string $authGuard): string
    {
        return URL::signedRoute(
            'offers.exports.download',
            ['exportId' => $export->getKey(), 'authGuard' => $authGuard],
            absolute: false,
        );
    }
}
