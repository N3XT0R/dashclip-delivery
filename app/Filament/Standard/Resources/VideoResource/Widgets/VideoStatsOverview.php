<?php

namespace App\Filament\Standard\Resources\VideoResource\Widgets;

use App\Models\User;
use App\Repository\AssignmentRepository;
use App\Repository\VideoRepository;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VideoStatsOverview extends BaseWidget
{

    
    protected function getStats(): array
    {
        $videoRepository = app()->get(VideoRepository::class);
        $assignmentRepository = app()->get(AssignmentRepository::class);
        /**
         * @var User $user
         */
        $user = Filament::auth()->user();
        $videoCount = $videoRepository->getVideoCountForUser($user);
        $availableOffers = $assignmentRepository->getAvailableOffersCountForUser($user);
        $expiredOffers = $assignmentRepository->getExpiredOffersCountForUser($user);

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
