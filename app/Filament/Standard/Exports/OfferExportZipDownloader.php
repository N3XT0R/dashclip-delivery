<?php

declare(strict_types=1);

namespace App\Filament\Standard\Exports;

use App\Services\OfferExportFileService;
use Filament\Actions\Exports\Downloaders\Contracts\Downloader;
use Filament\Actions\Exports\Models\Export;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Streams the ZIP of an offer export; range requests keep working for resumed downloads.
 */
final readonly class OfferExportZipDownloader implements Downloader
{
    public function __construct(private OfferExportFileService $files)
    {
    }

    public function __invoke(Export $export): BinaryFileResponse
    {
        return response()->download(
            $this->files->absoluteZipPath($export),
            $export->file_name.'.zip',
            ['Cache-Control' => 'private, no-store'],
        );
    }
}
