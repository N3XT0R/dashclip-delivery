<?php

namespace App\Filament\Resources;

use App\Enum\Channel\ApplicationEnum;
use App\Filament\Resources\ChannelApplicationResource\Pages;
use App\Models\ChannelApplication;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class ChannelApplicationResource extends Resource
{
    protected static ?string $model = ChannelApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;


    public static function getPluralLabel(): ?string
    {
        return __('filament.admin_channel_application.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin_channel_application.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\Select::make('channel_id')
                    ->relationship('channel', 'name'),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->default(ApplicationEnum::PENDING->value),
                Forms\Components\Textarea::make('note')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Channel Application')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('filament.admin_channel_application.table.columns.applicant')
                    ->translateLabel()
                    ->searchable(),
                Tables\Columns\TextColumn::make('channel.name')
                    ->label('filament.admin_channel_application.table.columns.channel')
                    ->translateLabel()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('filament.admin_channel_application.table.columns.status')
                    ->translateLabel()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('filament.admin_channel_application.table.columns.submitted_at')
                    ->translateLabel()
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('filament.admin_channel_application.table.columns.updated_at')
                    ->translateLabel()
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
            ])->defaultSort('updated_at', 'desc');
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
            'index' => Pages\ListChannelApplications::route('/'),
            'create' => Pages\CreateChannelApplication::route('/create'),
            'edit' => Pages\EditChannelApplication::route('/{record}/edit'),
        ];
    }
}
