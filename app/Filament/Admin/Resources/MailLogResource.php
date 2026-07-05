<?php

namespace App\Filament\Admin\Resources;

use App\Models\MailLog;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MailLogResource extends Resource
{
    protected static ?string $model = MailLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'filament.admin.navigation.system';
    protected static ?string $label = 'filament.admin.labels.mail_log';

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('filament.admin.navigation.system');
    }

    public static function getModelLabel(): string
    {
        return __('filament.admin.labels.mail_log');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('direction')
                    ->label(__('filament.admin.labels.direction'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('to')
                    ->label(__('filament.admin.labels.recipient'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('subject')
                    ->label(__('filament.admin.labels.subject'))
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
                    ->label(__('filament.admin.labels.sent_at'))
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),

                TextColumn::make('replied_at')
                    ->label(__('filament.admin.labels.replied_at'))
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bounced_at')
                    ->label(__('filament.admin.labels.bounced_at'))
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\MailLogResource\Pages\ListMailLogs::route('/'),
            'view' => \App\Filament\Admin\Resources\MailLogResource\Pages\ViewMailLog::route('/{record}'),
        ];
    }
}
