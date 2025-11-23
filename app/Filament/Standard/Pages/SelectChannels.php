<?php

namespace App\Filament\Standard\Pages;

use App\Models\Channel;
use App\Models\User;
use App\Repository\ChannelRepository;
use BackedEnum;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
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

    public function mount(ChannelRepository $channelRepository): void
    {
        /**
         * @var User $user
         */
        $user = Filament::auth()->user();
        $this->channels = $this->getChannelRepository()->getUserAssignedChannels($user)->pluck('channel_id')->toArray();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('channels')
                    ->label('Verfügbare Kanäle')
                    ->options($this->getChannelRepository()->getActiveChannels()->pluck('name', 'id'))
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->statePath('channels');
    }

    public function save(): void
    {
        auth()->user()->assignedChannels()->sync($this->channels ?? []);

        Notification::make()
            ->title('Kanäle gespeichert')
            ->success()
            ->send();
    }

    public function getHeaderActions(): array
    {
        return [
            AttachAction::make()
                ->label('Kanal hinzufügen')
                ->schema([
                    Select::make('recordId')
                        ->label('Channel')
                        ->options(Channel::pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),
                ]),
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
