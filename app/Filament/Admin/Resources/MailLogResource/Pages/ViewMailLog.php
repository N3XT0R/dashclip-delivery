<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\MailLogResource\Pages;

use App\Filament\Admin\Resources\MailLogResource;
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
                TextEntry::make('direction')->label(__('filament.admin.labels.direction')),
                TextEntry::make('to')->label(__('filament.admin.labels.recipient')),
                TextEntry::make('subject')->label(__('filament.admin.labels.subject')),
                TextEntry::make('status')->label(__('filament.admin.labels.status')),
                TextEntry::make('created_at')->dateTime('d.m.Y H:i')->label(__('filament.admin.labels.sent')),
                TextEntry::make('bounced_at')->dateTime('d.m.Y H:i')->label(__('filament.admin.labels.bounced')),
                TextEntry::make('replied_at')->dateTime('d.m.Y H:i')->label(__('filament.admin.labels.replied_at')),
                TextEntry::make('meta')
                    ->label(__('filament.admin.labels.headers'))
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
                ->label(__('filament.admin.labels.show_email_content'))
                ->icon('heroicon-o-eye')
                ->modalHeading(__('filament.admin.labels.email_content'))
                ->modalWidth('5xl')
                ->modalContent(function (MailLog $record) {
                    $content = $record->meta['content'] ?? '';

                    if (trim($content) === '') {
                        return new HtmlString(sprintf('<em>%s</em>', e(__('filament.admin.messages.empty_email_content'))));
                    }

                    $isHtml = str_contains($content, '<html') || preg_match('/<\/?[a-z][\s>]/i', $content);

                    if ($isHtml) {
                        return new HtmlString(
                            <<<HTML
                            <div style="
                                background:#ffffff;
                                color:#1e293b;
                                font-family: system-ui, sans-serif;
                                font-size: 15px;
                                padding:20px;
                                border-radius:8px;
                                overflow-y:auto;
                                max-height:70vh;
                                line-height:1.6;
                                box-shadow: inset 0 0 0 1px #e2e8f0;
                            ">
                                {$content}
                            </div>
                        HTML
                        );
                    }

                    $escaped = e($content);
                    return new HtmlString(
                        <<<HTML
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
                ->visible(fn (MailLog $record) => !empty($record->meta['content']))
                ->modalSubmitAction(false),
        ];
    }
}
