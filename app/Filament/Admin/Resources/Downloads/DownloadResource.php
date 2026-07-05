<?php

namespace App\Filament\Admin\Resources\Downloads;

use App\Filament\Admin\Resources\Downloads\Pages\ListDownloads;
use App\Models\Download;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DownloadResource extends Resource
{
    protected static ?string $model = Download::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static string|\UnitEnum|null $navigationGroup = 'filament.admin.navigation.media';
    protected static ?string $modelLabel = 'filament.admin.labels.download';
    protected static ?string $pluralModelLabel = 'filament.admin.labels.downloads';

    public static function getModelLabel(): string
    {
        return __('filament.admin.labels.download');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.admin.labels.downloads');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('downloaded_at', 'desc')
            ->columns([
                TextColumn::make('assignment.id')
                    ->label(__('filament.admin.labels.assignment_id'))
                    ->sortable(),
                TextColumn::make('assignment.status')
                    ->label(__('filament.admin.labels.status'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('ip')
                    ->url(function (Download $download) {
                        return sprintf('https://utrace.me/?query=%s', $download->getAttribute('ip'));
                    }, true)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('assignment.channel.name')
                    ->label(__('filament.admin.labels.channel'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('assignment.video.original_name')
                    ->url(function (Download $download) {
                        return $download->getAttribute('assignment')->getAttribute('video')->getAttribute(
                            'preview_url'
                        );
                    }, true)
                    ->label(__('filament.admin.labels.video'))
                    ->sortable(),
                TextColumn::make('downloaded_at')
                    ->label(__('filament.admin.labels.downloaded_at'))
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDownloads::route('/'),
        ];
    }
}
