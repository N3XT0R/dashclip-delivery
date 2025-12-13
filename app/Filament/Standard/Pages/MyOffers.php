<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages;

use App\Filament\Standard\Pages\Widgets\MyOffersStats;
use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Download;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

class MyOffers extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected static ?string $navigationIcon = Heroicon::VideoCamera;

    protected static ?string $title = 'filament.my_offers.title';

    protected static ?string $navigationLabel = 'filament.my_offers.navigation_label';

    protected static ?string $navigationGroup = 'nav.media';

    protected static string $view = 'filament.standard.pages.my-offers';

    public string $tab = 'available';

    public function getTitle(): string
    {
        return __(static::$title);
    }

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public static function getNavigationGroup(): string
    {
        return __(static::$navigationGroup);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('kanalbetreiber') ?? false;
    }

    #[Computed]
    public function availableAssignments(): Builder
    {
        return $this->baseQuery()
            ->whereNotIn('status', [StatusEnum::PICKEDUP->value, StatusEnum::EXPIRED->value, StatusEnum::REJECTED->value])
            ->where('expires_at', '>', now());
    }

    #[Computed]
    public function downloadedAssignments(): Builder
    {
        return $this->baseQuery()
            ->where('status', StatusEnum::PICKEDUP->value)
            ->orderByDesc(
                Download::query()
                    ->select('downloaded_at')
                    ->whereColumn('assignments.id', 'downloads.assignment_id')
                    ->latest()
                    ->limit(1)
            );
    }

    #[Computed]
    public function expiredAssignments(): Builder
    {
        return $this->baseQuery()
            ->where('expires_at', '<=', now())
            ->orderByDesc('expires_at');
    }

    #[Computed]
    public function returnedAssignments(): Builder
    {
        return $this->baseQuery()
            ->where('status', StatusEnum::REJECTED->value)
            ->orderByDesc('updated_at');
    }

    protected function baseQuery(): Builder
    {
        return Assignment::query()
            ->where('channel_id', $this->getChannel()?->getKey())
            ->with(['video.clips', 'video.user', 'batch', 'downloads']);
    }

    protected function getChannel(): ?Channel
    {
        return auth()->user()?->channels()->first();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('filament.my_offers.table.heading'))
            ->query(fn () => match ($this->tab) {
                'downloaded' => $this->downloadedAssignments(),
                'expired' => $this->expiredAssignments(),
                'returned' => $this->returnedAssignments(),
                default => $this->availableAssignments(),
            })
            ->columns($this->columnsForTab())
            ->actions($this->actionsForTab())
            ->bulkActions($this->bulkActionsForTab())
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    protected function columnsForTab(): array
    {
        return match ($this->tab) {
            'downloaded' => [
                TextColumn::make('video.title')
                    ->label(__('filament.my_offers.table.columns.title'))
                    ->wrap(),
                TextColumn::make('video.user.name')
                    ->label(__('filament.my_offers.table.columns.from')),
                TextColumn::make('created_at')
                    ->label(__('filament.my_offers.table.columns.offered_at'))
                    ->dateTime('d.m.Y'),
                TextColumn::make('downloads.0.downloaded_at')
                    ->label(__('filament.my_offers.table.columns.downloaded_at'))
                    ->dateTime('d.m.Y H:i'),
            ],
            'expired' => [
                TextColumn::make('video.title')
                    ->label(__('filament.my_offers.table.columns.title')),
                TextColumn::make('video.user.name')
                    ->label(__('filament.my_offers.table.columns.from')),
                TextColumn::make('created_at')
                    ->label(__('filament.my_offers.table.columns.offered_at'))
                    ->dateTime('d.m.Y'),
                TextColumn::make('expires_at')
                    ->label(__('filament.my_offers.table.columns.expired_at'))
                    ->dateTime('d.m.Y'),
                BadgeColumn::make('downloads')
                    ->label(__('filament.my_offers.table.columns.was_downloaded'))
                    ->state(fn (Assignment $record) => $record->downloads->isNotEmpty() ? 'Ja' : 'Nein')
                    ->color(fn (Assignment $record) => $record->downloads->isNotEmpty() ? 'success' : 'gray'),
                TextColumn::make('downloads.0.downloaded_at')
                    ->label(__('filament.my_offers.table.columns.downloaded_at'))
                    ->dateTime('d.m.Y')
                    ->placeholder('-'),
            ],
            'returned' => [
                TextColumn::make('video.title')
                    ->label(__('filament.my_offers.table.columns.title')),
                TextColumn::make('video.user.name')
                    ->label(__('filament.my_offers.table.columns.from')),
                TextColumn::make('created_at')
                    ->label(__('filament.my_offers.table.columns.offered_at'))
                    ->dateTime('d.m.Y'),
                TextColumn::make('updated_at')
                    ->label(__('filament.my_offers.table.columns.returned_at'))
                    ->dateTime('d.m.Y'),
                TextColumn::make('batch.note')
                    ->label(__('filament.my_offers.table.columns.return_reason'))
                    ->placeholder('-'),
            ],
            default => [
                TextColumn::make('video.title')
                    ->label(__('filament.my_offers.table.columns.title'))
                    ->wrap(),
                TextColumn::make('video.user.name')
                    ->label(__('filament.my_offers.table.columns.from')),
                TextColumn::make('expires_at')
                    ->label(__('filament.my_offers.table.columns.expires_at'))
                    ->dateTime('d.m.Y')
                    ->formatStateUsing(fn ($state) => $state?->format('d.m.Y') . ' (' . now()->diffInDays($state, false) . 'd)')
                    ->color(fn ($state) => $state && now()->diffInDays($state, false) < 3 ? 'danger' : 'success'),
                BadgeColumn::make('status')
                    ->label(__('filament.my_offers.table.columns.status'))
                    ->colors([
                        'success' => [StatusEnum::NOTIFIED->value, StatusEnum::QUEUED->value],
                        'gray' => StatusEnum::PICKEDUP->value,
                    ])
                    ->formatStateUsing(fn (string $state) => __(sprintf('filament.my_offers.status.%s', $state))),
            ],
        };
    }

    protected function actionsForTab(): array
    {
        $detailAction = Action::make('details')
            ->label(__('filament.my_offers.actions.details'))
            ->modalHeading(__('filament.my_offers.actions.details'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('filament.my_offers.actions.close'))
            ->infolist(fn (Assignment $record) => $this->detailsInfolist($record))
            ->icon(Heroicon::Eye);

        $downloadAction = Action::make('download')
            ->label(__('filament.my_offers.actions.download'))
            ->icon(Heroicon::ArrowDownTray)
            ->requiresConfirmation()
            ->visible(fn () => $this->tab === 'available' || $this->tab === 'downloaded')
            ->action(fn () => null);

        return match ($this->tab) {
            'downloaded' => [$detailAction, $downloadAction],
            'expired', 'returned' => [$detailAction],
            default => [$detailAction, $downloadAction],
        };
    }

    protected function bulkActionsForTab(): array
    {
        if ($this->tab !== 'available') {
            return [];
        }

        return [
            BulkAction::make('download_selected')
                ->label(__('filament.my_offers.actions.download_selected'))
                ->icon(Heroicon::ArrowDownTray)
                ->action(fn () => null),
        ];
    }

    protected function detailsInfolist(Assignment $record): Infolist
    {
        return Infolist::make()
            ->schema([
                Section::make(__('filament.my_offers.details.video'))
                    ->schema([
                        TextEntry::make('video.title')
                            ->label(__('filament.my_offers.table.columns.title')),
                        TextEntry::make('video.filename')
                            ->label(__('filament.my_offers.details.filename')),
                        TextEntry::make('video.duration')
                            ->label(__('filament.my_offers.details.duration'))
                            ->formatStateUsing(fn ($state) => gmdate('i:s', (int) $state)),
                        TextEntry::make('video.bytes')
                            ->label(__('filament.my_offers.details.filesize'))
                            ->formatStateUsing(fn ($state) => number_format((int) $state / 1024 / 1024, 2) . ' MB'),
                    ])->columns(2),
                Section::make(__('filament.my_offers.details.clips'))
                    ->schema([
                        RepeatableEntry::make('video.clips')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('role')->label(__('filament.my_offers.details.role')),
                                    TextEntry::make('start_time')
                                        ->label(__('filament.my_offers.details.start')),
                                    TextEntry::make('end_time')
                                        ->label(__('filament.my_offers.details.end')),
                                    TextEntry::make('submitted_by')
                                        ->label(__('filament.my_offers.details.submitted_by')),
                                    TextEntry::make('note')
                                        ->label(__('filament.my_offers.details.notes'))
                                        ->columnSpanFull(),
                                ]),
                            ])
                            ->grid(1)
                            ->columns(1),
                    ])->collapsible(),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        if ($this->tab !== 'available') {
            return [];
        }

        return [
            MyOffersStats::class,
        ];
    }
}
