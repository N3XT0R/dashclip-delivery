<?php

namespace App\Filament\Standard\Resources\AssignmentResource\Pages;

use App\Filament\Standard\Resources\AssignmentResource;
use App\Filament\Standard\Widgets\ChannelWidgets\AvailableOffersStatsWidget;
use App\Filament\Standard\Widgets\ChannelWidgets\DownloadedOffersStatsWidget;
use App\Filament\Standard\Widgets\ChannelWidgets\ExpiredOffersStatsWidget;
use App\Models\Assignment;
use App\Models\Channel;
use App\Repository\UserRepository;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ListAssignments extends ListRecords
{
    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }

    public function hasResourceBreadcrumbs(): bool
    {
        return false;
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

    public function table(Table $table): Table
    {
        $channel = $this->getCurrentChannel();
        if (!$channel) {
            return $table->query(Assignment::query()->where('channel_id', -1));
        }

        return $table
            ->query(Assignment::query()->where('channel_id', $channel->getKey()));
    }

    public function getTabs(): array
    {
        return [
            'available' => Tab::make('available')->label(__('my_offers.tabs.available')),
            'downloaded' => Tab::make('downloaded')->label(__('my_offers.tabs.downloaded')),
            'expired' => Tab::make('expired')->label(__('my_offers.tabs.expired')),
            'returned' => Tab::make('returned')->label(__('my_offers.tabs.returned')),
        ];
    }
}
