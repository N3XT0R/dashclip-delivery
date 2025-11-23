<?php

namespace App\Filament\Standard\Pages;

use App\Models\Channel;
use App\Models\Team;
use App\Repository\ChannelRepository;
use App\Repository\TeamRepository;
use BackedEnum;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SelectChannels extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $title = 'Kanäle auswählen';
    protected static ?string $navigationLabel = 'Channels';
    protected string $view = 'filament.standard.pages.select-channels';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    public ?array $channels = [];


    protected function getChannelRepository(): ChannelRepository
    {
        return app(ChannelRepository::class);
    }

    public function getHeaderActions(): array
    {
        $teamRepository = app(TeamRepository::class);
        /**
         * @var Team $tenant
         */
        $tenant = Filament::getTenant();
        $isOwner = $teamRepository->isUserOwnerOfTeam(auth()->user(), $tenant);
        $availableChannels = $this->getChannelRepository()
            ->getActiveChannels()
            ->whereNotIn('id', $tenant->assignedChannels()->pluck('channels.id'));

        return [
            AttachAction::make()
                ->label('Kanäle hinzufügen')
                ->schema([
                    Select::make('recordId')
                        ->label('Channel')
                        ->options($availableChannels->pluck('name', 'id'))
                        ->searchable()
                        ->multiple()
                        ->preload()
                        ->required(),
                ])
                ->action(function (array $data) use ($tenant) {
                    $tenant->assignedChannels()->attach($data['recordId']);
                })
                ->visible($isOwner),
        ];
    }

    public function table(Table $table): Table
    {
        $teamRepository = app(TeamRepository::class);
        /**
         * @var Team $tenant
         */
        $tenant = Filament::getTenant();
        $isOwner = $teamRepository->isUserOwnerOfTeam(auth()->user(), $tenant);

        return $table
            ->recordTitle('Kanal')
            ->columns([
                TextColumn::make('name')->label('Name'),
                TextColumn::make('youtube_name')
                    ->label('Youtube-Kanal')
                    ->formatStateUsing(fn($state) => $state ? '@'.$state : '-')
                    ->url(fn($record) => $record->youtube_name
                        ? 'https://www.youtube.com/@'.$record->youtube_name
                        : null
                    )
                    ->openUrlInNewTab()
                    ->limit(40),
                TextColumn::make('quota')
                    ->label('Quota (Videos/Woche)')
                    ->icon('heroicon-m-pencil-square')
                    ->iconPosition(IconPosition::After)
                    ->action(
                        EditAction::make('editQuota')
                            ->label('Quota bearbeiten')
                            ->schema([
                                TextInput::make('quota')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                            ])
                            ->action(function (Channel $record, array $data) use ($tenant) {
                                $tenant->assignedChannels()
                                    ->updateExistingPivot($record->getKey(), [
                                        'quota' => $data['quota'],
                                    ]);
                            })
                            ->visible($isOwner)
                    )
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Entfernen')
                    ->action(function (Channel $record) use ($tenant) {
                        $tenant->assignedChannels()->detach($record->getKey());
                    })
                    ->visible($isOwner),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        /**
         * @var Team $tenant
         */
        $tenant = Filament::getTenant();

        return $tenant
            ->assignedChannels()
            ->getQuery();
    }
}
