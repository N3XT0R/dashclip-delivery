<?php

namespace App\Filament\Admin\Resources\Videos\Pages;

use App\Filament\Admin\Resources\Videos\VideoResource;
use App\Models\Video;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;

class ViewVideo extends ViewRecord
{
    protected static string $resource = VideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextEntry::make('original_name')->label(__('filament.admin.labels.file_name')),
                TextEntry::make('ext')->label(__('filament.admin.labels.ext')),
                TextEntry::make('bytes')
                    ->label(__('filament.admin.labels.size'))
                    ->formatStateUsing(fn ($state): string => $state ? Number::fileSize((int)$state) : '-'),
                TextEntry::make('disk')->label(__('filament.admin.labels.disk')),
                TextEntry::make('preferred_channel')
                    ->label(__('filament.admin.labels.preferred_channel'))
                    ->placeholder('-')
                    ->getStateUsing(fn (Video $record): ?string => $record->clipsWithTrashed
                        ->first()?->preferredChannel?->getAttribute('name')),
                TextEntry::make('blurred')
                    ->label(__('filament.admin.labels.blurred'))
                    ->getStateUsing(fn (Video $record): string => $record->hasBlurredPlates()
                        ? __('filament.admin.labels.blurred_yes')
                        : __('filament.admin.labels.blurred_no')),
                TextEntry::make('hash')->label('Hash')->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label(__('filament.admin.labels.created'))
                    ->dateTime('d.m.Y H:i'),
                KeyValueEntry::make('meta')
                    ->label(__('filament.admin.labels.meta'))
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
