<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers\Table;

use App\Models\Assignment;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;

final class Columns
{
    /**
     * @return array<int, TextColumn>
     */
    public function make(Page $page): array
    {
        return [
            $this->videoTitle(),
            $this->uploaders($page),
            $this->expiresAt($page),
            $this->status($page),
            $this->createdAt($page),
            $this->downloadedAt($page),
            $this->wasDownloaded($page),
            $this->returnedAt($page),
            $this->returnReason($page),
        ];
    }

    /* -----------------------------------------------------------------
     | Public component factories
     | -----------------------------------------------------------------
     */

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

    public function uploaders(Page $page): TextColumn
    {
        return TextColumn::make('video.clips.user.name')
            ->label(__('my_offers.table.columns.uploader'))
            ->formatStateUsing(
                fn(Assignment $record): string => $this->resolveUploaders($record, $page)
            )
            ->limit(30);
    }

    public function expiresAt(Page $page): TextColumn
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

    public function status(Page $page): TextColumn
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

    public function createdAt(Page $page): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('my_offers.table.columns.offered_at'))
            ->dateTime('d.m.Y H:i')
            ->sortable()
            ->visible(
                fn(): bool => in_array($page->activeTab, ['downloaded', 'expired', 'returned'], true)
            );
    }

    public function downloadedAt(Page $page): TextColumn
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
                fn(): bool => in_array($page->activeTab, ['downloaded', 'expired'], true)
            );
    }

    public function wasDownloaded(Page $page): TextColumn
    {
        return TextColumn::make('was_downloaded')
            ->label(__('my_offers.table.columns.was_downloaded'))
            ->badge()
            ->formatStateUsing(
                fn(Assignment $record): string => $record->downloads->isNotEmpty()
                    ? __('common.yes')
                    : __('common.no')
            )
            ->color(
                fn(Assignment $record): string => $record->downloads->isNotEmpty()
                    ? 'success'
                    : 'gray'
            )
            ->visible(
                fn(): bool => $page->activeTab === 'expired'
            );
    }

    public function returnedAt(Page $page): TextColumn
    {
        return TextColumn::make('updated_at')
            ->label(__('my_offers.table.columns.returned_at'))
            ->dateTime('d.m.Y H:i')
            ->sortable()
            ->visible(
                fn(): bool => $page->activeTab === 'returned'
            );
    }

    public function returnReason(Page $page): TextColumn
    {
        return TextColumn::make('return_reason')
            ->label(__('my_offers.table.columns.return_reason'))
            ->default('—')
            ->limit(50)
            ->visible(
                fn(): bool => $page->activeTab === 'returned'
            );
    }

    /* -----------------------------------------------------------------
     | Private helpers (intentionally not part of the public API)
     | -----------------------------------------------------------------
     */

    private function resolveUploaders(Assignment $record, Page $page): string
    {
        $key = $page->activeTab === 'returned'
            ? 'user.display_name'
            : 'user.name';

        return $record->video->clips
            ->pluck($key)
            ->unique()
            ->filter()
            ->implode(', ')
            ?: '—';
    }

    private function expiresDescription(Assignment $record, Page $page): string
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

    private function expiresColor(Assignment $record, Page $page): string
    {
        if ($page->activeTab !== 'available' || !$record->expires_at) {
            return 'gray';
        }

        return now()->diffInDays($record->expires_at) < 3
            ? 'danger'
            : 'success';
    }
}
