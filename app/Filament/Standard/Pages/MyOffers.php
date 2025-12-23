<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages;

use App\Enum\StatusEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Standard\Widgets\ChannelWidgets\AvailableOffersStatsWidget;
use App\Filament\Standard\Widgets\ChannelWidgets\DownloadedOffersStatsWidget;
use App\Filament\Standard\Widgets\ChannelWidgets\ExpiredOffersStatsWidget;
use App\Models\Assignment;
use App\Models\Channel;
use App\Repository\UserRepository;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class MyOffers extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithActions;
    use HasTabs;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'nav.channel_owner';

    protected static ?int $navigationSort = 10;


    public static function getNavigationLabel(): string
    {
        return __('my_offers.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __(static::$navigationGroup);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return trans('common.channel') . ': ' . $this->getCurrentChannel()?->name;
    }

    public function getTitle(): string
    {
        return __('my_offers.title');
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if (!$user) {
            return false;
        }

        return $user->hasRole(RoleEnum::CHANNEL_OPERATOR->value);
    }

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getTabsContentComponent(),
            EmbeddedTable::make(),
        ]);
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

    public function getTabs(): array
    {
        return [
            'available' => Tab::make(__('my_offers.tabs.available'))
                ->modifyQueryUsing(function (Builder $query): Builder {
                    return $query
                        ->whereIn('status', [StatusEnum::QUEUED->value, StatusEnum::NOTIFIED->value])
                        ->where(function (Builder $query): void {
                            $query->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        })
                        ->latest('updated_at');
                }),
            'downloaded' => Tab::make(__('my_offers.tabs.downloaded'))
                ->modifyQueryUsing(function (Builder $query): Builder {
                    return $query
                        ->where('status', StatusEnum::PICKEDUP->value)
                        ->whereHas('downloads')
                        ->join('downloads', 'assignments.id', '=', 'downloads.assignment_id')
                        ->orderByDesc('downloads.downloaded_at')
                        ->select('assignments.*');
                }),
            'expired' => Tab::make(__('my_offers.tabs.expired'))
                ->modifyQueryUsing(fn(Builder $query): Builder => $query
                    ->where('status', StatusEnum::EXPIRED->value)
                    ->latest('updated_at')),
            'returned' => Tab::make(__('my_offers.tabs.returned'))
                ->modifyQueryUsing(fn(Builder $query): Builder => $query
                    ->where('status', StatusEnum::REJECTED->value)
                    ->latest('updated_at')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'available';
    }

    public function table(Table $table): Table
    {
        $channel = $this->getCurrentChannel();

        if (!$channel) {
            return $table->query(Assignment::query()->where('channel_id', -1));
        }

        return $table
            ->query(
                Assignment::query()
                    ->where('channel_id', $channel->id)
                    ->with(['video.clips.user', 'downloads'])
            )
            ->modifyQueryUsing(fn(Builder $query): Builder => $this->modifyQueryWithActiveTab($query))
            ->columns([
                TextColumn::make('video.original_name')
                    ->label(__('my_offers.table.columns.video_title'))
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn(Assignment $record): string => $record->video->original_name ?? ''),

                TextColumn::make('video.clips.user.name')
                    ->label(__('my_offers.table.columns.uploader'))
                    ->formatStateUsing(function (Assignment $record): string {
                        $uploaderKey = $this->activeTab === 'returned'
                            ? 'user.display_name'
                            : 'user.name';

                        $uploaders = $record->video->clips
                            ->pluck($uploaderKey)
                            ->unique()
                            ->filter()
                            ->implode(', ');

                        return $uploaders ?: '—';
                    })
                    ->limit(30),

                TextColumn::make('expires_at')
                    ->label(fn(): string => $this->activeTab === 'expired'
                        ? __('my_offers.table.columns.expired_at')
                        : __('my_offers.table.columns.valid_until'))
                    ->dateTime('d.m.Y H:i')
                    ->description(function (Assignment $record): string {
                        if ($this->activeTab !== 'available' || !$record->expires_at) {
                            return '';
                        }

                        $diff = now()->diffInDays($record->expires_at);

                        if ($diff < 0) {
                            return trans('common.expired');
                        }

                        if ($diff < 1) {
                            $hours = now()->diffInHours($record->expires_at);
                            return __('my_offers.table.columns.remaining_hours', ['hours' => max(0, $hours)]);
                        }

                        return __('my_offers.table.columns.remaining_days', ['days' => (int)$diff]);
                    })
                    ->color(function (Assignment $record): string {
                        if ($this->activeTab !== 'available' || !$record->expires_at) {
                            return 'gray';
                        }

                        $diff = now()->diffInDays($record->expires_at);

                        if ($diff < 3) {
                            return 'danger';
                        }

                        return 'success';
                    })
                    ->sortable()
                    ->visible(fn(): bool => in_array($this->activeTab, ['available', 'expired'])),

                TextColumn::make('status')
                    ->label(__('my_offers.table.columns.status'))
                    ->badge()
                    ->formatStateUsing(function (Assignment $record): string {
                        if ($record->downloads->isNotEmpty()) {
                            return __('my_offers.table.status_badges.downloaded');
                        }

                        return __('my_offers.table.status_badges.available');
                    })
                    ->color(fn(Assignment $record): string => $record->downloads->isNotEmpty() ? 'success' : 'warning')
                    ->visible(fn(): bool => $this->activeTab === 'available'),

                TextColumn::make('created_at')
                    ->label(__('my_offers.table.columns.offered_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->visible(fn(): bool => in_array($this->activeTab, ['downloaded', 'expired', 'returned'])),

                TextColumn::make('downloads.downloaded_at')
                    ->label(__('my_offers.table.columns.downloaded_at'))
                    ->formatStateUsing(function (Assignment $record): string {
                        $latestDownload = $record->downloads->sortByDesc('downloaded_at')->first();

                        return $latestDownload?->downloaded_at?->format('d.m.Y H:i') ?? '—';
                    })
                    ->sortable()
                    ->visible(fn(): bool => in_array($this->activeTab, ['downloaded', 'expired'])),

                TextColumn::make('was_downloaded')
                    ->label(__('my_offers.table.columns.was_downloaded'))
                    ->badge()
                    ->formatStateUsing(function (Assignment $record): string {
                        return $record->downloads->isNotEmpty()
                            ? __('common.yes')
                            : __('common.no');
                    })
                    ->color(fn(Assignment $record): string => $record->downloads->isNotEmpty() ? 'success' : 'gray')
                    ->visible(fn(): bool => $this->activeTab === 'expired'),

                TextColumn::make('updated_at')
                    ->label(__('my_offers.table.columns.returned_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->visible(fn(): bool => $this->activeTab === 'returned'),

                TextColumn::make('return_reason')
                    ->label(__('my_offers.table.columns.return_reason'))
                    ->default('—')
                    ->limit(50)
                    ->visible(fn(): bool => $this->activeTab === 'returned'),
            ])
            ->recordActions([
                Action::make('view_details')
                    ->label(__('my_offers.table.actions.view_details'))
                    ->icon('heroicon-m-eye')
                    ->modalHeading(__('my_offers.modal.title'))
                    ->modalWidth(Width::FourExtraLarge)
                    ->schema(fn(Assignment $record): Schema => $this->getDetailsInfolist($record))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen'),

                Action::make('download_again')
                    ->label(__('my_offers.table.actions.download_again'))
                    ->icon('heroicon-m-arrow-path')
                    ->color('gray')
                    ->url(fn(Assignment $record): string => '#') // TODO: Implement download URL
                    ->openUrlInNewTab()
                    ->visible(fn(): bool => $this->activeTab === 'downloaded'),

                ViewAction::make('download')
                    ->label(__('my_offers.table.actions.download'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('primary')
                    ->url(fn(Assignment $record): string => '#') // TODO: Implement download URL
                    ->openUrlInNewTab()
                    ->visible(fn(): bool => $this->activeTab === 'available'),
            ])
            ->bulkActions([
                BulkAction::make('download_selected')
                    ->label(fn(Collection $records): string => __('my_offers.table.bulk_actions.download_selected',
                        ['count' => $records->count()]))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('primary')
                    ->action(function (Collection $records) {
                        // TODO: Implement bulk download
                    })
                    ->visible(fn(): bool => $this->activeTab === 'available'),
            ])
            ->selectCurrentPageOnly($this->activeTab === 'available')
            ->emptyStateHeading(__('my_offers.table.empty_state.heading'))
            ->emptyStateDescription(match ($this->activeTab) {
                'downloaded' => __('my_offers.messages.no_videos_downloaded'),
                'expired' => __('my_offers.messages.no_expired_offers'),
                'returned' => __('my_offers.messages.no_returned_offers'),
                default => __('my_offers.table.empty_state.description'),
            });
    }

    protected function getDetailsInfolist(Assignment $assignment): Schema
    {
        return Schema::make()
            ->livewire($this)
            ->state([
                'video' => $assignment->video,
                'clips' => $assignment->video->clips,
            ])
            ->schema([
                Section::make(__('my_offers.modal.preview.heading'))
                    ->schema([
                        ViewField::make('preview')
                            ->view('filament.standard.components.video-preview')
                            ->viewData([
                                'video' => $assignment->video,
                            ]),
                    ])
                    ->collapsible(),

                Section::make(__('my_offers.modal.metadata.heading'))
                    ->schema([
                        TextEntry::make('video.human_readable_size')
                            ->label(__('my_offers.modal.metadata.file_size'))
                            ->default('—'),
                        TextEntry::make('video.original_name')
                            ->label(__('my_offers.modal.metadata.filename'))
                            ->default('—'),
                    ])
                    ->columns(3),

                Section::make(__('my_offers.modal.clips.heading'))
                    ->schema([
                        ViewField::make('clips')
                            ->view('filament.standard.components.clips-table')
                            ->viewData([
                                'clips' => $assignment->video->clips()->orderBy('start_sec')->get(),
                            ]),
                    ])
                    ->collapsible()
                    ->hidden(fn(): bool => $assignment->video->clips->isEmpty()),
            ]);
    }

    protected function getCurrentChannel(): ?Channel
    {
        $user = app(UserRepository::class)->getCurrentUser();

        if (!$user) {
            return null;
        }
        return $user->channels()->firstOrFail();
    }

}
