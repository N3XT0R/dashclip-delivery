<?php

namespace App\Filament\Standard\Resources\VideoResource\Widgets;

use App\Models\User;
use App\Services\VideoStatsService;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class VideoStatsOverview extends BaseWidget
{


    protected function getStats(): array
    {
        /**
         * @var User $user
         */
        $user = Filament::auth()->user();
        $statsService = app(VideoStatsService::class);

        return [
            $statsService->getVideoStats($user),
            $statsService->getDownloadedVideoStats($user),
            $statsService->getAvailableOffersStats($user),
            $statsService->getExpiredOffersStats($user),
        ];
    }
}
