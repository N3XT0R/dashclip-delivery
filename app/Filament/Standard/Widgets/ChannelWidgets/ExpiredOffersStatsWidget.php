<?php

declare(strict_types=1);

namespace App\Filament\Standard\Widgets\ChannelWidgets;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Channel;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ExpiredOffersStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $channel = $this->getCurrentChannel();

        if (!$channel) {
            return [
                Stat::make(__('my_offers.stats.expired.label'), 0),
                Stat::make(__('my_offers.stats.expired.downloaded_count'), 0),
                Stat::make(__('my_offers.stats.expired.missed_count'), 0),
            ];
        }

        $expiredQuery = Assignment::query()
            ->where('channel_id', $channel->id)
            ->where('status', StatusEnum::EXPIRED->value);

        $totalExpired = $expiredQuery->count();

        $downloadedBeforeExpiry = (clone $expiredQuery)
            ->whereHas('downloads')
            ->count();

        $missedCount = $totalExpired - $downloadedBeforeExpiry;

        $percentageMissed = $totalExpired > 0
            ? (int)round(($missedCount / $totalExpired) * 100)
            : 0;

        return [
            Stat::make(__('my_offers.stats.expired.label'), (string)$totalExpired)
                ->description('Insgesamt abgelaufen')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray')
                ->chart($this->getExpiredChartData($channel)),

            Stat::make(__('my_offers.stats.expired.downloaded_count'), (string)$downloadedBeforeExpiry)
                ->description('Noch heruntergeladen')
                ->descriptionIcon('heroicon-m-check')
                ->color('success'),

            Stat::make(__('my_offers.stats.expired.missed_count'), (string)$missedCount)
                ->description("{$percentageMissed}% verpasst")
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }

    protected function getCurrentChannel(): ?Channel
    {
        $user = Filament::auth()->user();

        if (!$user) {
            return null;
        }

        $tenant = Filament::getTenant();

        if ($tenant instanceof \App\Models\Team) {
            return $tenant->assignedChannels()->first();
        }

        return Channel::query()
            ->whereHas('assignedTeams', fn(Builder $query) => $query->where('teams.id', $tenant?->id))
            ->first();
    }

    protected function getExpiredChartData(Channel $channel): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $count = Assignment::query()
                ->where('channel_id', $channel->id)
                ->where('status', StatusEnum::EXPIRED->value)
                ->whereDate('updated_at', $date->toDateString())
                ->count();

            $data[] = $count;
        }

        return $data;
    }
}
