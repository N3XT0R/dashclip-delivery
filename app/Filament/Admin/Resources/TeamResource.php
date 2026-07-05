<?php

namespace App\Filament\Admin\Resources;

use App\Models\Team;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'filament.admin.navigation.system';

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'filament.admin.labels.team';
    protected static ?string $pluralModelLabel = 'filament.admin.labels.teams';

    public static function getModelLabel(): string
    {
        return __('filament.admin.labels.team');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.admin.labels.teams');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(),
                Forms\Components\TextInput::make('name')
                    ->required(),
                Select::make('owner_id')
                    ->label(__('filament.admin.labels.owner'))
                    ->relationship('users', 'name')
                    ->preload()
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label(__('filament.admin.labels.owner'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Actions\EditAction::make(),
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
            'index' => \App\Filament\Admin\Resources\TeamResource\Pages\ListTeams::route('/'),
            'create' => \App\Filament\Admin\Resources\TeamResource\Pages\CreateTeam::route('/create'),
            'edit' => \App\Filament\Admin\Resources\TeamResource\Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
