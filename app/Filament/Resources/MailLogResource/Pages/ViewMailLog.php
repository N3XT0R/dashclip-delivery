<?php

declare(strict_types=1);

namespace App\Filament\Resources\MailLogResource\Pages;

use App\Filament\Resources\MailLogResource;
use App\Models\MailLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewMailLog extends ViewRecord
{
    protected static string $resource = MailLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextEntry::make('to')->label('Empfänger'),
                TextEntry::make('subject')->label('Betreff'),
                TextEntry::make('status')->label('Status'),
                TextEntry::make('created_at')->dateTime('d.m.Y H:i')->label('Gesendet'),
                TextEntry::make('bounced_at')->dateTime('d.m.Y H:i')->label('Bounced'),
                TextEntry::make('replied_at')->dateTime('d.m.Y H:i')->label('Geantwortet'),
                TextEntry::make('meta')
                    ->label('Header')
                    ->getStateUsing(function (MailLog $record) {
                        $headers = $record->meta['headers'] ?? [];

                        if (is_array($headers)) {
                            $lines = implode("\n", $headers);
                        } else {
                            $lines = (string)$headers;
                        }

                        $escaped = e($lines);

                        return <<<HTML
                            <pre style="
                                background:#f8fafc;
                                color:#1e293b;
                                padding:12px;
                                border-radius:6px;
                                font-family: monospace;
                                font-size: 13px;
                                overflow-x:auto;
                                white-space:pre-wrap;
                                line-height:1.4;
                            "><code>{$escaped}</code></pre>
                        HTML;
                    })
                    ->columnSpanFull()
                    ->copyable()
                    ->html(),
            ]);
    }
}
