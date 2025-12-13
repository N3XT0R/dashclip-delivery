<?php

declare(strict_types=1);

namespace App\Filament\Standard\Widgets;

use App\Enum\StatusEnum;
use App\Services\Queries\AssignmentQueryInterface;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExpiredOffersStatsWidget extends StatsOverviewWidget
{
    protected ?AssignmentQueryInterface $query = null;

    public function mount(AssignmentQueryInterface $query): void
    {
        $this->query = $query;
    }

    public function getQuery(): AssignmentQueryInterface
    {
        return $this->query ?? app(AssignmentQueryInterface::class);
    }

    protected function getStats(): array
    {
        $expiredQuery = $this->query->expired();
        $total = $expiredQuery->count();
        $downloaded = (clone $expiredQuery)->where('status', StatusEnum::PICKEDUP->value)->count();

        return [
            Stat::make(__('filament.my_offers.widgets.expired.total'), $total),
            Stat::make(__('filament.my_offers.widgets.expired.downloaded'), $downloaded),
            Stat::make(__('filament.my_offers.widgets.expired.missed'), max($total - $downloaded, 0)),
        ];
    }
}
