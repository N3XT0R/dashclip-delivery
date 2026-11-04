<?php

declare(strict_types=1);

namespace App\Filament\Standard\Exports;

use App\Enum\OfferExportFormatEnum;
use App\Models\Assignment;
use App\Models\Channel;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Str;

/**
 * Exports offers as a ZIP with their videos; info.csv is written by the archive builder.
 */
class OfferExporter extends Exporter
{
    protected static ?string $model = Assignment::class;

    /** Filament needs at least one column; the archive content does not depend on them. */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('video.original_name')->label(__('my_offers.export.column_video')),
        ];
    }

    public function getFormats(): array
    {
        return [OfferExportFormatEnum::Zip];
    }

    public function getFileName(Export $export): string
    {
        $channel = Channel::query()->find($this->options['channel_id'] ?? null);

        return sprintf('videos_%s_%s_%s', Str::slug((string) ($channel?->name ?: 'offers')), now()->format('Y-m-d'), $export->getKey());
    }

    public static function getCompletedNotificationTitle(Export $export): string
    {
        return $export->successful_rows > 0
            ? __('my_offers.export.completed.title')
            : __('my_offers.export.failed.title');
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        if ($export->successful_rows === 0) {
            return __('my_offers.export.failed.body');
        }

        $body = trans_choice('my_offers.export.completed.ready', $export->total_rows, [
            'ready' => $export->successful_rows,
            'total' => $export->total_rows,
        ]);
        $skipped = $export->getFailedRowsCount();

        return $skipped > 0
            ? $body.' '.trans_choice('my_offers.export.completed.skipped', $skipped, ['count' => $skipped])
            : $body;
    }
}
