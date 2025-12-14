<?php

declare(strict_types=1);

namespace App\Filament\Standard\Widgets\ChannelWidgets;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Channel;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class DownloadedOffersStatsWidget extends BaseChannelWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $channel = $this->getCurrentChannel();

        if (!$channel) {
            return [
                Stat::make(__('my_offers.stats.downloaded.label'), 0),
                Stat::make(__('my_offers.stats.downloaded.total'), 0),
                Stat::make(__('my_offers.stats.downloaded.avg_download_days_ago'), 0),
            ];
        }

        $downloadedQuery = Assignment::query()
            ->where('channel_id', $channel->id)
            ->where('status', StatusEnum::PICKEDUP->value)
            ->whereHas('downloads');

        $totalDownloaded = $downloadedQuery->count();

        $avgDaysAgo = (float)Assignment::query()
            ->where('channel_id', $channel->id)
            ->where('status', StatusEnum::PICKEDUP->value)
            ->whereHas('downloads')
            ->join('downloads', 'assignments.id', '=', 'downloads.assignment_id')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, downloads.downloaded_at, NOW()) / 86400) as avg_days_ago')
            ->value('avg_days_ago');

        $avgDaysFormatted = $avgDaysAgo ? (int)round($avgDaysAgo) : 0;

        $thisWeekCount = (clone $downloadedQuery)
            ->whereHas('downloads', function (Builder $query) {
                $query->where('downloaded_at', '>=', now()->startOfWeek());
            })
            ->count();

        return [
            Stat::make(__('my_offers.stats.downloaded.label'), (string)$totalDownloaded)
                ->description('Insgesamt heruntergeladen')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart($this->getDownloadedChartData($channel)),

            Stat::make('Diese Woche', (string)$thisWeekCount)
                ->description('Neue Downloads')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),

            Stat::make(__('my_offers.stats.downloaded.avg_download_days_ago'), (string)$avgDaysFormatted)
                ->description('Durchschnitt')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }

    protected function getDownloadedChartData(Channel $channel): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $count = Assignment::query()
                ->where('channel_id', $channel->id)
                ->where('status', StatusEnum::PICKEDUP->value)
                ->whereHas('downloads', function (Builder $query) use ($date) {
                    $query->whereDate('downloaded_at', $date->toDateString());
                })
                ->count();

            $data[] = $count;
        }

        return $data;
    }
}
