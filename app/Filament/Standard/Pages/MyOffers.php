<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages;

use App\Enum\StatusEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Standard\Widgets\AvailableOffersStatsWidget;
use App\Filament\Standard\Widgets\DownloadedOffersStatsWidget;
use App\Filament\Standard\Widgets\ExpiredOffersStatsWidget;
use App\Models\Assignment;
use App\Models\Channel;
use App\Repository\UserRepository;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Enums\Width;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;
use UnitEnum;

class MyOffers extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected string $view = 'filament.standard.pages.my-offers';

    protected static string|UnitEnum|null $navigationGroup = 'nav.channel_owner';

    protected static ?int $navigationSort = 10;

    public string $activeTab = 'available';

    public static function getNavigationLabel(): string
    {
        return __('my_offers.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __(static::$navigationGroup);
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

    protected function getHeaderWidgets(): array
    {
        $user = app(UserRepository::class)->getCurrentUser();
        $channel = $user->channels()->first();
        return [
            AvailableOffersStatsWidget::make(['channel' => $channel]),
            DownloadedOffersStatsWidget::make(['channel' => $channel]),
            ExpiredOffersStatsWidget::make(['channel' => $channel]),
        ];
    }

    public function getTabs(): array
    {
        return [
            'available' => __('my_offers.tabs.available'),
            'downloaded' => __('my_offers.tabs.downloaded'),
            'expired' => __('my_offers.tabs.expired'),
            'returned' => __('my_offers.tabs.returned'),
        ];
    }

    public function table(Table $table): Table
    {
        $channel = $this->getCurrentChannel();

        if (!$channel) {
            return $table->query(Assignment::query()->whereRaw('1 = 0'));
        }

        return match ($this->activeTab) {
            'available' => $this->availableTable($table, $channel),
            'downloaded' => $this->downloadedTable($table, $channel),
            'expired' => $this->expiredTable($table, $channel),
            'returned' => $this->returnedTable($table, $channel),
            default => $this->availableTable($table, $channel),
        };
    }

    protected function availableTable(Table $table, Channel $channel): Table
    {
        return $table
            ->query(
                Assignment::query()
                    ->where('channel_id', $channel->id)
                    ->whereIn('status', [StatusEnum::QUEUED->value, StatusEnum::NOTIFIED->value])
                    ->where(function (Builder $query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->with(['video.clips.user', 'downloads'])
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('video.title')
                    ->label(__('my_offers.table.columns.video_title'))
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn(Assignment $record): string => $record->video->title ?? ''),

                TextColumn::make('video.clips.user.name')
                    ->label(__('my_offers.table.columns.uploader'))
                    ->formatStateUsing(function (Assignment $record): string {
                        $uploaders = $record->video->clips->pluck('user.name')->unique()->filter()->implode(', ');
                        return $uploaders ?: '—';
                    })
                    ->limit(30),

                TextColumn::make('expires_at')
                    ->label(__('my_offers.table.columns.valid_until'))
                    ->dateTime('d.m.Y H:i')
                    ->description(function (Assignment $record): string {
                        if (!$record->expires_at) {
                            return '';
                        }

                        $diff = now()->diffInDays($record->expires_at, false);

                        if ($diff < 0) {
                            return 'Abgelaufen';
                        }

                        if ($diff < 1) {
                            $hours = now()->diffInHours($record->expires_at, false);
                            return __('my_offers.table.columns.remaining_hours', ['hours' => max(0, $hours)]);
                        }

                        return __('my_offers.table.columns.remaining_days', ['days' => (int)$diff]);
                    })
                    ->color(function (Assignment $record): string {
                        if (!$record->expires_at) {
                            return 'gray';
                        }

                        $diff = now()->diffInDays($record->expires_at, false);

                        if ($diff < 3) {
                            return 'danger';
                        }

                        return 'success';
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('my_offers.table.columns.status'))
                    ->badge()
                    ->formatStateUsing(function (Assignment $record): string {
                        if ($record->downloads->isNotEmpty()) {
                            return __('my_offers.table.status_badges.downloaded');
                        }

                        return __('my_offers.table.status_badges.available');
                    })
                    ->color(function (Assignment $record): string {
                        return $record->downloads->isNotEmpty() ? 'success' : 'warning';
                    }),
            ])
            ->actions([
                ViewAction::make('view_details')
                    ->label(__('my_offers.table.actions.view_details'))
                    ->icon('heroicon-m-eye')
                    ->modalHeading(__('my_offers.modal.title'))
                    ->modalWidth(Width::FourExtraLarge)
                    ->schema(fn(Assignment $record): Schema => $this->getDetailsInfolist($record))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen'),

                ViewAction::make('download')
                    ->label(__('my_offers.table.actions.download'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('primary')
                    ->url(fn(Assignment $record): string => '#') // TODO: Implement download URL
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkAction::make('download_selected')
                    ->label(fn(Collection $records): string => __('my_offers.table.bulk_actions.download_selected',
                        ['count' => $records->count()]))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('primary')
                    ->action(function (Collection $records) {
                        // TODO: Implement bulk download
                    }),
            ])
            ->selectCurrentPageOnly()
            ->emptyStateHeading(__('my_offers.table.empty_state.heading'))
            ->emptyStateDescription(__('my_offers.table.empty_state.description'));
    }

    protected function downloadedTable(Table $table, Channel $channel): Table
    {
        return $table
            ->query(
                Assignment::query()
                    ->where('channel_id', $channel->id)
                    ->where('status', StatusEnum::PICKEDUP->value)
                    ->whereHas('downloads')
                    ->with(['video.clips.user', 'downloads'])
                    ->latest('updated_at')
            )
            ->columns([
                TextColumn::make('video.title')
                    ->label(__('my_offers.table.columns.video_title'))
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn(Assignment $record): string => $record->video->title ?? ''),

                TextColumn::make('video.clips.user.name')
                    ->label(__('my_offers.table.columns.uploader'))
                    ->formatStateUsing(function (Assignment $record): string {
                        $uploaders = $record->video->clips->pluck('user.name')->unique()->filter()->implode(', ');
                        return $uploaders ?: '—';
                    })
                    ->limit(30),

                TextColumn::make('created_at')
                    ->label(__('my_offers.table.columns.offered_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('downloads.downloaded_at')
                    ->label(__('my_offers.table.columns.downloaded_at'))
                    ->formatStateUsing(function (Assignment $record): string {
                        $latestDownload = $record->downloads->sortByDesc('downloaded_at')->first();
                        return $latestDownload?->downloaded_at?->format('d.m.Y H:i') ?? '—';
                    })
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make('view_details')
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
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading(__('my_offers.table.empty_state.heading'))
            ->emptyStateDescription('Sie haben noch keine Videos heruntergeladen.');
    }

    protected function expiredTable(Table $table, Channel $channel): Table
    {
        return $table
            ->query(
                Assignment::query()
                    ->where('channel_id', $channel->id)
                    ->where('status', StatusEnum::EXPIRED->value)
                    ->with(['video.clips.user', 'downloads'])
                    ->latest('updated_at')
            )
            ->columns([
                TextColumn::make('video.title')
                    ->label(__('my_offers.table.columns.video_title'))
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn(Assignment $record): string => $record->video->title ?? ''),

                TextColumn::make('video.clips.user.name')
                    ->label(__('my_offers.table.columns.uploader'))
                    ->formatStateUsing(function (Assignment $record): string {
                        $uploaders = $record->video->clips->pluck('user.name')->unique()->filter()->implode(', ');
                        return $uploaders ?: '—';
                    })
                    ->limit(30),

                TextColumn::make('created_at')
                    ->label(__('my_offers.table.columns.offered_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label(__('my_offers.table.columns.expired_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('was_downloaded')
                    ->label(__('my_offers.table.columns.was_downloaded'))
                    ->badge()
                    ->formatStateUsing(function (Assignment $record): string {
                        return $record->downloads->isNotEmpty()
                            ? __('my_offers.table.status_badges.yes')
                            : __('my_offers.table.status_badges.no');
                    })
                    ->color(function (Assignment $record): string {
                        return $record->downloads->isNotEmpty() ? 'success' : 'gray';
                    }),

                TextColumn::make('downloads.downloaded_at')
                    ->label(__('my_offers.table.columns.downloaded_at'))
                    ->formatStateUsing(function (Assignment $record): string {
                        $latestDownload = $record->downloads->sortByDesc('downloaded_at')->first();
                        return $latestDownload?->downloaded_at?->format('d.m.Y H:i') ?? '—';
                    })
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make('view_details')
                    ->label(__('my_offers.table.actions.view_details'))
                    ->icon('heroicon-m-eye')
                    ->modalHeading(__('my_offers.modal.title'))
                    ->modalWidth(Width::FourExtraLarge)
                    ->schema(fn(Assignment $record): Schema => $this->getDetailsInfolist($record))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen'),
            ])
            ->emptyStateHeading(__('my_offers.table.empty_state.heading'))
            ->emptyStateDescription('Sie haben keine abgelaufenen Angebote.');
    }

    protected function returnedTable(Table $table, Channel $channel): Table
    {
        return $table
            ->query(
                Assignment::query()
                    ->where('channel_id', $channel->id)
                    ->where('status', StatusEnum::REJECTED->value)
                    ->with(['video.clips.user', 'downloads'])
                    ->latest('updated_at')
            )
            ->columns([
                TextColumn::make('video.title')
                    ->label(__('my_offers.table.columns.video_title'))
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn(Assignment $record): string => $record->video->title ?? ''),

                TextColumn::make('video.clips.user.name')
                    ->label(__('my_offers.table.columns.uploader'))
                    ->formatStateUsing(function (Assignment $record): string {
                        $uploaders = $record->video->clips->pluck('user.name')->unique()->filter()->implode(', ');
                        return $uploaders ?: '—';
                    })
                    ->limit(30),

                TextColumn::make('created_at')
                    ->label(__('my_offers.table.columns.offered_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label(__('my_offers.table.columns.returned_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('return_reason')
                    ->label(__('my_offers.table.columns.return_reason'))
                    ->default('—')
                    ->limit(50),
            ])
            ->recordActions([
                ViewAction::make('view_details')
                    ->label(__('my_offers.table.actions.view_details'))
                    ->icon('heroicon-m-eye')
                    ->modalHeading(__('my_offers.modal.title'))
                    ->modalWidth(Width::FourExtraLarge)
                    ->schema(fn(Assignment $record): Schema => $this->getDetailsInfolist($record))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen'),
            ])
            ->emptyStateHeading(__('my_offers.table.empty_state.heading'))
            ->emptyStateDescription('Sie haben keine zurückgewiesenen Angebote.');
    }

    protected function getDetailsInfolist(Assignment $assignment): Schema
    {
        return Schema::make()
            ->state([
                'video' => $assignment->video,
                'clips' => $assignment->video->clips,
            ])
            ->schema([
                \Filament\Schemas\Components\Section::make(__('my_offers.modal.preview.heading'))
                    ->schema([
                        ViewField::make('preview')
                            ->view('filament.standard.components.video-preview')
                            ->viewData([
                                'video' => $assignment->video,
                            ]),
                    ])
                    ->collapsible(),

                \Filament\Schemas\Components\Section::make(__('my_offers.modal.metadata.heading'))
                    ->schema([
                        TextEntry::make('video.file_size')
                            ->label(__('my_offers.modal.metadata.file_size'))
                            ->formatStateUsing(fn($state): string => Number::fileSize($state))
                            ->default('—'),

                        TextEntry::make('video.duration')
                            ->label(__('my_offers.modal.metadata.duration'))
                            ->formatStateUsing(fn($state): string => $this->formatDuration($state))
                            ->default('—'),

                        TextEntry::make('video.filename')
                            ->label(__('my_offers.modal.metadata.filename'))
                            ->default('—'),
                    ])
                    ->columns(3),

                \Filament\Schemas\Components\Section::make(__('my_offers.modal.clips.heading'))
                    ->schema([
                        ViewField::make('clips')
                            ->view('filament.standard.components.clips-table')
                            ->viewData([
                                'clips' => $assignment->video->clips,
                            ]),
                    ])
                    ->collapsible()
                    ->hidden(fn(): bool => $assignment->video->clips->isEmpty()),
            ]);
    }

    protected function getCurrentChannel(): ?Channel
    {
        $user = Filament::auth()->user();

        if (!$user) {
            return null;
        }

        $tenant = Filament::getTenant();

        if ($tenant instanceof \App\Models\Team) {
            return $tenant->assignedChannels()->first();
        }

        return Channel::query()
            ->whereHas('assignedTeams', fn(Builder $query) => $query->where('teams.id', $tenant?->id))
            ->first();
    }

    protected function formatDuration(?int $seconds): string
    {
        if (!$seconds || $seconds <= 0) {
            return '—';
        }

        $minutes = intdiv($seconds, 60);
        $secs = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $secs);
    }
}
