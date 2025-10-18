<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MailLogResource\Pages;
use App\Models\MailLog;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MailLogResource extends Resource
{
    protected static ?string $model = MailLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static ?string $label = 'Mail Log';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('direction')
                    ->label('Richtung')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('to')
                    ->label('Empfänger')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('subject')
                    ->label('Betreff')
                    ->wrap()
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'sent',
                        'warning' => 'replied',
                        'danger' => 'bounced',
                    ])
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Gesendet am')
                    ->label('Sent at')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),

                TextColumn::make('replied_at')
                    ->label('Antwort am')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bounced_at')
                    ->label('Bounce am')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMailLogs::route('/'),
            'view' => Pages\ViewMailLog::route('/{record}'),
        ];
    }
}
