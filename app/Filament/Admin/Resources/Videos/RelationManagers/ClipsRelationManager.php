<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Videos\RelationManagers;

use App\Application\Clips\GetPreviewUrl;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
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
                TextColumn::make('start_sec')->label(__('filament.admin.labels.start')),
                TextColumn::make('end_sec')->label(__('filament.admin.labels.end')),
                TextColumn::make('submitted_by')->label(__('filament.admin.labels.submitted_by')),
                TextColumn::make('created_at')->dateTime()->since()->dateTimeTooltip(),
            ])
            ->headerActions([])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-m-eye')
                    ->schema($this->getSchemaForViewAction()),
                Action::make('preview')
                    ->label(__('filament.admin.labels.preview'))
                    ->icon('heroicon-m-play')
                    ->url(fn ($record) => app(GetPreviewUrl::class)->handle($record))
                    ->visible(fn ($record) => null !== $record->preview_path)
                    ->openUrlInNewTab()
            ])
            ->toolbarActions([]);
    }

    protected function getSchemaForViewAction(): array
    {
        return [
            TextInput::make('id')
                ->label(__('filament.admin.labels.clip_id'))
                ->disabled(),
            TextInput::make('original_name')
                ->label(__('filament.admin.labels.video_name'))
                ->formatStateUsing(fn ($record) => $record->video?->original_name)
                ->disabled(),
            TextInput::make('start_sec')
                ->label(__('filament.admin.labels.start_time'))
                ->disabled(),
            TextInput::make('end_sec')
                ->label(__('filament.admin.labels.end_time'))
                ->disabled(),
            Textarea::make('note')
                ->label(__('filament.admin.labels.note'))
                ->disabled(),
            TextInput::make('submitted_by')
                ->label(__('filament.admin.labels.submitted_by'))
                ->disabled(),
            ViewField::make('video_preview')
                ->label(__('filament.admin.labels.preview'))
                ->view('filament.forms.components.video-preview')
                ->columnSpanFull(),
        ];
    }
}
