<?php

declare(strict_types=1);

namespace App\Filament\Standard\Widgets;

use App\Services\Queries\AssignmentQueryInterface;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DownloadedOffersStatsWidget extends StatsOverviewWidget
{
    
    protected function getStats(): array
    {
        $downloadedQuery = app(AssignmentQueryInterface::class)->downloaded();

        return [
            Stat::make(__('filament.my_offers.widgets.downloaded.total'), $downloadedQuery->count()),
            Stat::make(__('filament.my_offers.widgets.downloaded.average_date'),
                $downloadedQuery->average('updated_at')),
            Stat::make(__('filament.my_offers.widgets.downloaded.trend'),
                __('filament.my_offers.widgets.downloaded.trend_placeholder')),
        ];
    }
}
