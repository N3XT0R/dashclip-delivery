<?php

namespace App\Filament\Standard\Resources;

use App\Filament\Standard\Resources\ChannelTeamResource\Pages;
use App\Models\Pivots\ChannelTeamPivot;
use BackedEnum;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChannelTeamResource extends Resource
{
    protected static ?string $model = ChannelTeamPivot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Channels';


    protected static ?string $modelLabel = 'Kanal-Zuweisung';
    protected static ?string $pluralModelLabel = 'Kanal-Zuweisungen';

    public static function getNavigationGroup(): string
    {
        return __('nav.settings');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('manageChannels', $record->team);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('manageChannels', $record->team);
    }

    public static function canCreate(): bool
    {
        $tenant = Filament::getTenant();
        return auth()->user()->can('manageChannels', $tenant);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('channel_id')
                    ->relationship('channel', 'name')
                    ->required(),
                Forms\Components\TextInput::make('quota')
                    ->numeric()
                    ->minValue(0)
                    ->default(10)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Kanäle')
            ->columns([
                Tables\Columns\TextColumn::make('channel.name')
                    ->label('Name'),
                Tables\Columns\TextColumn::make('channel.youtube_name')
                    ->label('Youtube-Kanal')
                    ->inline()
                    ->formatStateUsing(fn($state) => $state ? '@'.$state : '-')
                    ->url(function (ChannelTeamPivot $record) {
                        $channel = $record->channel;
                        if ($channel->youtube_name) {
                            return 'https://www.youtube.com/@'.$record->youtube_name;
                        }

                        return null;
                    })
                    ->openUrlInNewTab()
                    ->limit(40),
                Tables\Columns\TextColumn::make('quota')
                    ->label('Quota (Videos/Woche)')
                    ->sortable()
                    ->inline(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Actions\EditAction::make()
                    ->modal(),
                Actions\DeleteAction::make()
                    ->modal(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChannelTeams::route('/'),
            'create' => Pages\CreateChannelTeam::route('/create'),
            'edit' => Pages\EditChannelTeam::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->where('team_id', $tenant?->getKey());
    }
}
