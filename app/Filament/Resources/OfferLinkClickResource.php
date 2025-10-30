<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferLinkClickResource\Pages;
use App\Models\OfferLinkClick;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class OfferLinkClickResource extends Resource
{
    protected static ?string $model = OfferLinkClick::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static ?string $label = 'Offer Link Clicks';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable(),
                Tables\Columns\TextColumn::make('batch.type')
                    ->label('Batch')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('channel.name')
                    ->label('Channel')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('clicked_at')
                    ->label('Clicked At')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(80)
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('batch_id')
                    ->label('Batch')
                    ->relationship('batch', 'type'),

                Tables\Filters\SelectFilter::make('channel_id')
                    ->label('Channel')
                    ->relationship('channel', 'name'),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name'),
            ])
            ->recordActions([
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('clicked_at', 'desc');
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
            'index' => Pages\ListOfferLinkClicks::route('/'),
            //'create' => Pages\CreateOfferLinkClick::route('/create'),
            //'edit' => Pages\EditOfferLinkClick::route('/{record}/edit'),
        ];
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }


    public static function canCreate(): bool
    {
        return false;
    }
}
