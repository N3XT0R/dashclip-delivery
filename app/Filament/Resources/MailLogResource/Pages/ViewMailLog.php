<?php

declare(strict_types=1);

namespace App\Filament\Resources\MailLogResource\Pages;

use App\Filament\Resources\MailLogResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewMailLog extends ViewRecord
{
    protected static string $resource = MailLogResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('to')->label('Empfänger'),
                TextEntry::make('subject')->label('Betreff'),
                TextEntry::make('status')->label('Status'),
                TextEntry::make('created_at')->dateTime('d.m.Y H:i')->label('Gesendet'),
                TextEntry::make('bounced_at')->dateTime('d.m.Y H:i')->label('Bounced'),
                TextEntry::make('replied_at')->dateTime('d.m.Y H:i')->label('Geantwortet'),
                TextEntry::make('meta')
                    ->label('Metadaten')
                    ->formatStateUsing(fn($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
            ]);
    }
}
