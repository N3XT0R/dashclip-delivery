<?php

declare(strict_types=1);

namespace App\Filament\Standard\Widgets;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Team;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class AvailableOffersStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?int $channel = null;

    protected function getStats(): array
    {
        dd($this->channel);
        $channel = $this->getCurrentChannel();

        if (!$channel) {
            return [
                Stat::make(__('my_offers.stats.available.label'), 0),
                Stat::make(__('my_offers.stats.available.downloaded_from_available'), 0),
                Stat::make(__('my_offers.stats.available.avg_validity_days'), 0),
            ];
        }

        $availableQuery = Assignment::query()
            ->where('channel_id', $channel->id)
            ->whereIn('status', [StatusEnum::QUEUED->value, StatusEnum::NOTIFIED->value])
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        $totalAvailable = $availableQuery->count();

        $downloadedFromAvailable = (clone $availableQuery)
            ->whereHas('downloads')
            ->count();

        $avgValidityDays = (float)(clone $availableQuery)
            ->whereNotNull('expires_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, NOW(), expires_at) / 86400) as avg_days')
            ->value('avg_days');

        $avgDaysFormatted = $avgValidityDays ? (int)round($avgValidityDays) : 0;

        return [
            Stat::make(__('my_offers.stats.available.label'), (string)$totalAvailable)
                ->description('Noch nicht abgelaufen')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('success')
                ->chart($this->getAvailableChartData($channel)),

            Stat::make(__('my_offers.stats.available.downloaded_from_available'), (string)$downloadedFromAvailable)
                ->description('Von verfügbaren')
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('primary'),

            Stat::make(__('my_offers.stats.available.avg_validity_days'), (string)$avgDaysFormatted)
                ->description('Durchschnittliche Gültigkeit')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }

    protected function getCurrentChannel(): ?Channel
    {
        $user = Filament::auth()->user();

        if (!$user) {
            return null;
        }

        $tenant = Filament::getTenant();

        if ($tenant instanceof Team) {
            return $tenant->assignedChannels()->first();
        }

        return Channel::query()
            ->whereHas('assignedTeams', fn(Builder $query) => $query->where('teams.id', $tenant?->id))
            ->first();
    }

    protected function getAvailableChartData(Channel $channel): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();

            $count = Assignment::query()
                ->where('channel_id', $channel->id)
                ->whereIn('status', [StatusEnum::QUEUED->value, StatusEnum::NOTIFIED->value])
                ->whereDate('created_at', '<=', $date->toDateString())
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
