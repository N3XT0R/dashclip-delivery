<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repository\AssignmentRepository;
use App\Repository\VideoRepository;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

readonly class VideoStatsService
{
    public function __construct(
        private VideoRepository $videoRepository,
        private AssignmentRepository $assignmentRepository,
    ) {
    }


    public function getVideoStats(User $user): Stat
    {
        return Stat::make('Videos', number_format($this->videoRepository->getVideoCountForUser($user)))
            ->description('Videos insgesamt')
            ->color('primary')
            ->icon('heroicon-m-film');
    }

    public function getDownloadedVideoStats(User $user): Stat
    {
        return Stat::make('Heruntergeladene Videos',
            Number::format($this->assignmentRepository->getPickedUpOffersCountForUser($user)))
            ->description('Videos insgesamt')
            ->color('primary')
            ->icon(Heroicon::OutlinedArrowDownTray);
    }


    public function getAvailableOffersStats(User $user): Stat
    {
        return Stat::make('Verfügbare Offers',
            Number::format($this->assignmentRepository->getAvailableOffersCountForUser($user)))
            ->description('bereit zum Versenden')
            ->color('success')
            ->icon('heroicon-m-sparkles');
    }

    public function getExpiredOffersStats(User $user): Stat
    {
        return Stat::make('Abgelaufene Offers',
            Number::format($this->assignmentRepository->getExpiredOffersCountForUser($user)))
            ->description('nicht mehr aktiv')
            ->color('gray')
            ->icon('heroicon-m-clock');
    }
}