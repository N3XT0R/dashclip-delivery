<?php

declare(strict_types=1);

namespace App\Filament\Resources\Videos\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
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
                TextColumn::make('start_sec')->label('Start'),
                TextColumn::make('end_sec')->label('End'),
                TextColumn::make('submitted_by')->label('Submitted By'),
                TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->headerActions([])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-m-eye')
                    ->schema($this->getSchemaForViewAction()),
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-m-play')
                    ->url(fn($record) => (string)$record->video?->getAttribute('preview_url'))
                    ->visible(fn($record) => null !== $record->video?->getAttribute('preview_url'))
                    ->openUrlInNewTab()
            ])
            ->toolbarActions([]);
    }

    protected function getSchemaForViewAction(): array
    {
        return [
            TextInput::make('id')
                ->label('Clip ID')
                ->disabled(),
            TextInput::make('original_name')
                ->label('Video Name')
                ->formatStateUsing(fn($record) => $record->video?->original_name)
                ->disabled(),
            TextInput::make('start_sec')
                ->label('Start Time')
                ->disabled(),
            TextInput::make('end_sec')
                ->label('End Time')
                ->disabled(),
            Textarea::make('note')
                ->label('Note')
                ->disabled(),
            TextInput::make('submitted_by')
                ->label('Submitted by')
                ->disabled(),
            ViewField::make('video_preview')
                ->label('Preview')
                ->view('filament.forms.components.video-preview')
                ->columnSpanFull(),
        ];
    }
}