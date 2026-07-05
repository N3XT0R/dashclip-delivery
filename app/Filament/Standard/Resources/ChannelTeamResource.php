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

    protected static ?string $navigationLabel = 'filament.standard.channel_team.navigation_label';


    protected static ?string $modelLabel = 'filament.standard.channel_team.model_label';
    protected static ?string $pluralModelLabel = 'filament.standard.channel_team.plural_model_label';

    public static function getNavigationLabel(): string
    {
        return __('filament.standard.channel_team.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament.standard.channel_team.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.standard.channel_team.plural_model_label');
    }

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
            ->recordTitleAttribute('channel.name')
            ->columns([
                Tables\Columns\TextColumn::make('channel.name')
                    ->label(__('filament.standard.channel_team.fields.name')),
                Tables\Columns\TextColumn::make('channel.youtube_name')
                    ->label(__('filament.standard.channel_team.fields.youtube_channel'))
                    ->inline()
                    ->formatStateUsing(fn ($state) => $state ? '@' . $state : '-')
                    ->url(function (ChannelTeamPivot $record) {
                        $channel = $record->channel;
                        if ($channel->youtube_name) {
                            return 'https://www.youtube.com/@' . $channel->youtube_name;
                        }

                        return null;
                    })
                    ->openUrlInNewTab()
                    ->limit(40),
                Tables\Columns\TextColumn::make('quota')
                    ->label(__('filament.standard.channel_team.fields.quota'))
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
