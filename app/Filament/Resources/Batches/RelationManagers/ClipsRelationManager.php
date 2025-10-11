<?php

namespace App\Filament\Resources\Batches\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClipsRelationManager extends RelationManager
{
    protected static string $relationship = 'clips';
    protected static ?string $title = 'Clips';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('video.original_name')
                    ->label('Video')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('start_time')->label('Start'),
                TextColumn::make('end_time')->label('End'),
                TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
