<?php

namespace App\Filament\Standard\Resources\VideoResource\Widgets;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Video;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VideoStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $userId = auth()->id();

        $videoQuery = Video::query()->where('user_id', $userId);
        $assignmentQuery = Assignment::query()->whereHas('video', fn($query) => $query->where('user_id', $userId));

        $videoCount = $videoQuery->count();
        $availableOffers = (clone $assignmentQuery)
            ->whereIn('status', StatusEnum::getReadyStatus())
            ->where(function ($query) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();
        $expiredOffers = (clone $assignmentQuery)
            ->where('status', StatusEnum::EXPIRED->value)
            ->count();

        return [
            Stat::make('Videos', number_format($videoCount))
                ->description('Videos insgesamt')
                ->color('primary')
                ->icon('heroicon-m-film'),

            Stat::make('Verfügbare Offers', number_format($availableOffers))
                ->description('bereit zum Versenden')
                ->color('success')
                ->icon('heroicon-m-sparkles'),

            Stat::make('Abgelaufene Offers', number_format($expiredOffers))
                ->description('nicht mehr aktiv')
                ->color('gray')
                ->icon('heroicon-m-clock'),
        ];
    }
}
