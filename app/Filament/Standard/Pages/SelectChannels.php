<?php

namespace App\Filament\Standard\Pages;

use App\Models\Channel;
use App\Repository\ChannelRepository;
use BackedEnum;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
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
        $availableChannels = $this->getChannelRepository()
            ->getActiveChannels()
            ->whereNotIn('id', auth()->user()->assignedChannels()->pluck('channels.id'));

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
                ->action(function (array $data) {
                    auth()->user()->assignedChannels()->attach($data['recordId']);
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Name'),
                TextColumn::make('description')
                    ->label('Beschreibung')
                    ->limit(40),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Entfernen')
                    ->action(function (Channel $record) {
                        auth()->user()->assignedChannels()->detach($record->getKey());
                    }),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return auth()->user()
            ->assignedChannels()
            ->getQuery();
    }
}
