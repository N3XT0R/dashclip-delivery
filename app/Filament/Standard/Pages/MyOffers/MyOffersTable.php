<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers;

final class MyOffersTable
{
    public static function make(
        Table $table,
        Page $page,
        ?Channel $channel,
    ): Table {
        return $table
            ->query(self::query($channel))
            ->modifyQueryUsing(fn(Builde $q) => $page->modifyQueryWithActiveTab($q))
            ->columns(MyOffersColumns::make($page))
            ->recordActions(MyOffersActions::make($page))
            ->bulkActions(MyOffersBulkActions::make($page))
            ->emptyStateHeading(__('my_offers.table.empty_state.heading'))
            ->emptyStateDescription(self::emptyState($page));
    }

    private static function query(?Channel $channel): Builder
    {
        return $channel
            ? Assignment::query()
                ->where('channel_id', $channel->id)
                ->with(['video.clips.user', 'downloads'])
            : Assignment::query()->whereRaw('1 = 0');
    }

    private static function emptyState(Page $page): string
    {
        return match ($page->activeTab) {
            'downloaded' => __('my_offers.messages.no_videos_downloaded'),
            'expired' => __('my_offers.messages.no_expired_offers'),
            'returned' => __('my_offers.messages.no_returned_offers'),
            default => __('my_offers.table.empty_state.description'),
        };
    }
}
