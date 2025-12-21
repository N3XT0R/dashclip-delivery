<?php

namespace App\Filament\Standard\Resources\AssignmentResource\Pages;

use App\Filament\Standard\Resources\AssignmentResource;
use App\Filament\Standard\Widgets\ChannelWidgets\AvailableOffersStatsWidget;
use App\Filament\Standard\Widgets\ChannelWidgets\DownloadedOffersStatsWidget;
use App\Filament\Standard\Widgets\ChannelWidgets\ExpiredOffersStatsWidget;
use App\Models\Channel;
use App\Repository\UserRepository;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListAssignments extends ListRecords
{
    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('my_offers.navigation_label');
    }

    public function getTitle(): string
    {
        return __('my_offers.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return trans('common.channel').': '.$this->getCurrentChannel()?->name;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AvailableOffersStatsWidget::class,
            DownloadedOffersStatsWidget::class,
            ExpiredOffersStatsWidget::class,
        ];
    }

    public function getWidgetData(): array
    {
        return [
            'channelId' => $this->getCurrentChannel()?->getKey(),
        ];
    }

    public function getCurrentChannel(): ?Channel
    {
        $user = app(UserRepository::class)->getCurrentUser();

        if (!$user) {
            return null;
        }
        return $user->channels()->firstOrFail();
    }
}
