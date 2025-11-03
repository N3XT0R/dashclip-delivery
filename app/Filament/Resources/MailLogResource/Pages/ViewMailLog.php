<?php

declare(strict_types=1);

namespace App\Filament\Resources\MailLogResource\Pages;

use App\Filament\Resources\MailLogResource;
use App\Models\MailLog;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ViewMailLog extends ViewRecord
{
    protected static string $resource = MailLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextEntry::make('direction')->label('Richtung'),
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('showContent')
                ->label('Show Email Content')
                ->icon('heroicon-o-eye')
                ->modalHeading('Email Content')
                ->modalWidth('5xl')
                ->modalContent(function (MailLog $record) {
                    $raw = $record->meta['content'] ?? null;

                    if (!$raw) {
                        return new HtmlString('<em>Kein Inhalt vorhanden</em>');
                    }

                    // MIME-Content nicht rendern, sondern sauber formatieren
                    $escaped = e($raw);

                    return new HtmlString(<<<HTML
                        <pre style="
                            background:#0f172a;
                            color:#e2e8f0;
                            padding:16px;
                            border-radius:8px;
                            font-family: monospace;
                            font-size: 13px;
                            overflow-x:auto;
                            white-space:pre-wrap;
                            line-height:1.5;
                            max-height:70vh;
                        ">{$escaped}</pre>
                    HTML
                    );
                })
                ->visible(fn(MailLog $record) => !empty($record->meta['content']))
                ->modalSubmitAction(false),
        ];
    }
}
