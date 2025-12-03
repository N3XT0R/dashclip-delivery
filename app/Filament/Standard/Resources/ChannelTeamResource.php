<?php

namespace App\Filament\Standard\Resources;

use App\Filament\Standard\Resources\ChannelTeamResource\Pages;
use App\Models\ChannelTeam;
use App\Models\Pivots\ChannelTeamPivot;
use BackedEnum;
use Filament\Actions;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ChannelTeamResource extends Resource
{
    protected static ?string $model = ChannelTeamPivot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Kanäle';

    protected static ?string $navigationLabel = 'Channels';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

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
                    ->inline()
                    ->action(
                        EditAction::make('editQuota')
                            ->label('Quota bearbeiten')
                            ->schema([
                                TextInput::make('quota')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                            ])
                            ->modalIcon('heroicon-m-pencil-square')
                            ->icon('heroicon-m-pencil-square')
                            ->iconPosition(IconPosition::After)
                            ->iconButton()
                    ),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Actions\EditAction::make()
                    ->modalHeading('Kanal editieren'),
                Actions\DeleteAction::make()
                    ->modalHeading('Kanal löschen'),
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
