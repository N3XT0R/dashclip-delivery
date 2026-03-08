<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Videos\RelationManagers;

use App\Filament\Admin\Resources\Assignments\AssignmentResource;
use App\Models\Assignment;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager
{
    // Eloquent relationship name on Video model
    protected static string $relationship = 'assignments';
    protected static ?string $title = 'Assignments';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('channel.name')
                    ->label('Channel')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('attempts')->numeric()->sortable(),
                TextColumn::make('expires_at')->dateTime()->since()->sortable(),
                TextColumn::make('last_notified_at')->dateTime()->since()->sortable()->toggleable(),
                TextColumn::make('created_at')->dateTime()->since()->sortable(),
            ])
            ->headerActions([]) // read-only
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn(Assignment $assignment) => AssignmentResource::getUrl('view', ['record' => $assignment]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([]);
    }
}
