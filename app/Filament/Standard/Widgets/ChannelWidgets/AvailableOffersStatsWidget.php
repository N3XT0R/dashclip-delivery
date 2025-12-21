<?php

declare(strict_types=1);

namespace App\Filament\Standard\Widgets\ChannelWidgets;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Channel;
use App\Repository\AssignmentRepository;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class AvailableOffersStatsWidget extends BaseChannelWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $stats = [];
        $assignmentRepo = app(AssignmentRepository::class);
        $channel = $this->getChannel();

        if (!$channel) {
            return [
                Stat::make(__('my_offers.stats.available.label'), 0),
            ];
        }

        $totalAvailable = $assignmentRepo->getAvailableOffersCountForChannel($channel);

        return [
            Stat::make(__('my_offers.stats.available.label'), (string)$totalAvailable)
                ->description('Noch nicht abgelaufen')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('success')
                ->chart($this->getAvailableChartData($channel))
        ];
    }

    protected function getAvailableChartData(Channel $channel): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();

            $count = Assignment::query()
                ->where('channel_id', $channel->id)
                ->whereIn('status', [StatusEnum::QUEUED->value, StatusEnum::NOTIFIED->value])
                ->whereDate('created_at', '<=', $date)
                ->where(function (Builder $query) use ($date) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', $date);
                })
                ->count();

            $data[] = $count;
        }

        return $data;
    }
}
