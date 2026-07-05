<?php

namespace App\Filament\Admin\Resources\Batches\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ClipsRelationManager extends RelationManager
{
    protected static string $relationship = 'clips';
    protected static ?string $title = 'filament.admin.labels.clips';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('filament.admin.labels.clips');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('video.original_name')
                    ->label(__('filament.admin.labels.video'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('submitted_by'),
                TextColumn::make('start_time')->label(__('filament.admin.labels.start')),
                TextColumn::make('end_time')->label(__('filament.admin.labels.end')),
                TextColumn::make('created_at')->dateTime()->since()->dateTimeTooltip(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
