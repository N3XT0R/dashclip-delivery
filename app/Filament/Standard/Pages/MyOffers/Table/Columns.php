<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers\Table;

use App\Filament\Standard\Pages\MyOffers;
use App\Models\Assignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;

final class Columns
{
    /**
     * @return array<int, TextColumn>
     */
    public function make(MyOffers $page): array
    {
        return [
            $this->videoPreview($page),
            $this->videoTitle(),
            $this->uploaders($page),
            $this->expiresAt($page),
            $this->status($page),
            $this->createdAt($page),
            $this->downloadedAt($page),
            $this->returnedAt($page),
        ];
    }

    /* -----------------------------------------------------------------
     | Public component factories
     | -----------------------------------------------------------------
     */

    public function videoPreview(MyOffers $page): ViewColumn
    {
        return ViewColumn::make('video_preview')
            ->label('Preview')
            ->view('filament.forms.components.video-preview')
            ->visible(
                fn(): bool => in_array($page->activeTab, ['available', 'downloaded'], true)
            );
    }

    public function videoTitle(): TextColumn
    {
        return TextColumn::make('video.original_name')
            ->label(__('my_offers.table.columns.video_title'))
            ->searchable()
            ->sortable()
            ->limit(40)
            ->tooltip(
                fn(Assignment $record): string => $record->video->original_name ?? ''
            );
    }

    public function uploaders(MyOffers $page): TextColumn
    {
        return TextColumn::make('video.clips.user.display_name')
            ->label(__('my_offers.table.columns.uploader'))
            ->formatStateUsing(
                fn(Assignment $record): string => $this->resolveUploaders($record, $page)
            )
            ->limit(30);
    }

    public function expiresAt(MyOffers $page): TextColumn
    {
        return TextColumn::make('expires_at')
            ->label(
                fn(): string => $page->activeTab === 'expired'
                    ? __('my_offers.table.columns.expired_at')
                    : __('my_offers.table.columns.valid_until')
            )
            ->dateTime('d.m.Y H:i')
            ->description(
                fn(Assignment $record): string => $this->expiresDescription($record, $page)
            )
            ->color(
                fn(Assignment $record): string => $this->expiresColor($record, $page)
            )
            ->sortable()
            ->visible(
                fn(): bool => in_array($page->activeTab, ['available', 'expired'], true)
            );
    }

    public function status(MyOffers $page): TextColumn
    {
        return TextColumn::make('status')
            ->label(__('my_offers.table.columns.status'))
            ->badge()
            ->formatStateUsing(
                fn(Assignment $record): string => $record->downloads->isNotEmpty()
                    ? __('my_offers.table.status_badges.downloaded')
                    : __('my_offers.table.status_badges.available')
            )
            ->color(
                fn(Assignment $record): string => $record->downloads->isNotEmpty()
                    ? 'success'
                    : 'warning'
            )
            ->visible(
                fn(): bool => $page->activeTab === 'available'
            );
    }

    public function createdAt(MyOffers $page): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('my_offers.table.columns.offered_at'))
            ->dateTime('d.m.Y H:i')
            ->sortable()
            ->visible(
                fn(): bool => in_array($page->activeTab, ['downloaded', 'expired', 'returned'], true)
            );
    }

    public function downloadedAt(MyOffers $page): TextColumn
    {
        return TextColumn::make('downloads.downloaded_at')
            ->label(__('my_offers.table.columns.downloaded_at'))
            ->formatStateUsing(
                fn(Assignment $record): string => $record->downloads
                    ->sortByDesc('downloaded_at')
                    ->first()
                    ?->downloaded_at
                    ?->format('d.m.Y H:i')
                    ?? '—'
            )
            ->sortable()
            ->visible(
                fn(): bool => $page->activeTab === 'downloaded'
            );
    }

    public function returnedAt(MyOffers $page): TextColumn
    {
        return TextColumn::make('updated_at')
            ->label(__('my_offers.table.columns.returned_at'))
            ->dateTime('d.m.Y H:i')
            ->sortable()
            ->visible(
                fn(): bool => $page->activeTab === 'returned'
            );
    }

    /* -----------------------------------------------------------------
     | Private helpers (intentionally not part of the public API)
     | -----------------------------------------------------------------
     */

    private function resolveUploaders(Assignment $record, MyOffers $page): string
    {
        return $record->video->clips
            ->pluck('user.display_name')
            ->unique()
            ->filter()
            ->implode(', ')
            ?: '—';
    }

    private function expiresDescription(Assignment $record, MyOffers $page): string
    {
        if ($page->activeTab !== 'available' || !$record->expires_at) {
            return '';
        }

        $diff = now()->diffInDays($record->expires_at);

        if ($diff < 0) {
            return trans('common.expired');
        }

        if ($diff < 1) {
            $hours = now()->diffInHours($record->expires_at);

            return __('my_offers.table.columns.remaining_hours', [
                'hours' => max(0, $hours),
            ]);
        }

        return __('my_offers.table.columns.remaining_days', [
            'days' => (int)$diff,
        ]);
    }

    private function expiresColor(Assignment $record, MyOffers $page): string
    {
        if ($page->activeTab !== 'available' || !$record->expires_at) {
            return 'gray';
        }

        return now()->diffInDays($record->expires_at) < 3
            ? 'danger'
            : 'success';
    }
}
