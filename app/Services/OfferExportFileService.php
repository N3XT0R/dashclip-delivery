<?php

declare(strict_types=1);

namespace App\Services;

use Filament\Actions\Exports\Models\Export;

/**
 * Knows where the ZIP and the list of packed offers of an offer export are stored.
 */
final class OfferExportFileService
{
    private const PACKED_FILE = 'packed.json';

    public function zipPath(Export $export): string
    {
        return $export->getFileDirectory().DIRECTORY_SEPARATOR.$export->file_name.'.zip';
    }

    public function absoluteZipPath(Export $export): string
    {
        return $export->getFileDisk()->path($this->zipPath($export));
    }

    public function hasZip(Export $export): bool
    {
        return $export->getFileDisk()->exists($this->zipPath($export));
    }

    /** @param list<int> $ids */
    public function writePackedIds(Export $export, array $ids): void
    {
        $export->getFileDisk()->put($this->packedPath($export), json_encode(array_values($ids), JSON_THROW_ON_ERROR));
    }

    /** @return list<int> */
    public function packedIds(Export $export): array
    {
        $json = $export->getFileDisk()->get($this->packedPath($export));

        return $json === null ? [] : array_map('intval', json_decode($json, true, flags: JSON_THROW_ON_ERROR));
    }

    private function packedPath(Export $export): string
    {
        return $export->getFileDirectory().DIRECTORY_SEPARATOR.self::PACKED_FILE;
    }
}
