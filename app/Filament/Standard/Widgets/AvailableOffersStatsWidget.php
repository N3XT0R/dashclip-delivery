<?php

declare(strict_types=1);

namespace App\Filament\Standard\Widgets;

use App\Enum\StatusEnum;
use App\Models\Channel;
use App\Services\Queries\AssignmentQueryInterface;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AvailableOffersStatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    public ?Channel $channel = null;

    public function __construct(public AssignmentQueryInterface $query)
    {
        parent::__construct();
    }

    protected function getStats(): array
    {
        $availableQuery = $this->query->available();
        $count = $availableQuery->count();
        $downloadedCount = (clone $availableQuery)->where('status', StatusEnum::PICKEDUP->value)->count();
        $averageDays = (clone $availableQuery)->average('expires_at');

        return [
            Stat::make(__('filament.my_offers.widgets.available.total'), $count),
            Stat::make(__('filament.my_offers.widgets.available.downloaded'), $downloadedCount),
            Stat::make(__('filament.my_offers.widgets.available.avg_validity'), $averageDays ? __('filament.my_offers.widgets.available.days', ['days' => 0]) : '—'),
        ];
    }
}
