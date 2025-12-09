<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChannelAppliationResource\Pages;
use App\Models\ChannelAppliation;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class ChannelAppliationResource extends Resource
{
    protected static ?string $model = ChannelAppliation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Channel Applications';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('Channel Applications')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Channel Applications')
            ->columns([
                Tables\Columns\TextColumn::make('Channel Applications')
                    ->searchable(),
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
            'index' => Pages\ListChannelAppliations::route('/'),
            'create' => Pages\CreateChannelAppliation::route('/create'),
            'edit' => Pages\EditChannelAppliation::route('/{record}/edit'),
        ];
    }
}
