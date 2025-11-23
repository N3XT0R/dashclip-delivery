<?php

namespace App\Filament\Standard\Resources\VideoResource\Widgets;

use App\Models\User;
use App\Repository\AssignmentRepository;
use App\Repository\VideoRepository;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class VideoStatsOverview extends BaseWidget
{


    protected function getStats(): array
    {
        /**
         * @var User $user
         */
        $user = Filament::auth()->user();

        return [
            $this->getVideoStats($user),
            $this->getDownloadedVideoStats($user),
            $this->getAvailableOffersStats($user),
            $this->getExpiredOffersStats($user),
        ];
    }

    protected function getVideoStats(User $user): Stat
    {
        $videoRepository = app()->get(VideoRepository::class);
        $videoCount = $videoRepository->getVideoCountForUser($user);

        return Stat::make('Videos', number_format($videoCount))
            ->description('Videos insgesamt')
            ->color('primary')
            ->icon('heroicon-m-film');
    }

    protected function getDownloadedVideoStats(User $user): Stat
    {
        $assignmentRepository = app()->get(AssignmentRepository::class);
        $pickedUpOffers = $assignmentRepository->getPickedUpOffersCountForUser($user);

        return Stat::make('Heruntergeladene Videos', Number::format($pickedUpOffers))
            ->description('Videos insgesamt')
            ->color('primary')
            ->icon(Heroicon::OutlinedArrowDownTray);
    }


    protected function getAvailableOffersStats(User $user): Stat
    {
        $assignmentRepository = app()->get(AssignmentRepository::class);
        $availableOffers = $assignmentRepository->getAvailableOffersCountForUser($user);

        return Stat::make('Verfügbare Offers', Number::format($availableOffers))
            ->description('bereit zum Versenden')
            ->color('success')
            ->icon('heroicon-m-sparkles');
    }

    protected function getExpiredOffersStats(User $user): Stat
    {
        $assignmentRepository = app()->get(AssignmentRepository::class);
        $expiredOffers = $assignmentRepository->getExpiredOffersCountForUser($user);

        return Stat::make('Abgelaufene Offers', Number::format($expiredOffers))
            ->description('nicht mehr aktiv')
            ->color('gray')
            ->icon('heroicon-m-clock');
    }
}
