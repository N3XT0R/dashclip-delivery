<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers\Table;

use App\Models\Assignment;
use App\Models\Channel;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final readonly class AssignmentTable
{
    public function __construct(
        private Columns $columns,
        private Actions $actions,
        private BulkActions $bulkActions,
    ) {
    }

    public function make(
        Table $table,
        Page $page,
        ?Channel $channel,
    ): Table {
        return $table
            ->query($this->baseQuery($channel))
            ->columns($this->columns->make($page))
            ->recordActions($this->actions->make($page))
            ->toolbarActions($this->bulkActions->make($page))
            ->selectCurrentPageOnly($page->activeTab === 'available')
            ->emptyStateHeading(__('my_offers.table.empty_state.heading'))
            ->emptyStateDescription(
                $this->emptyStateDescription($page)
            );
    }

    private function baseQuery(?Channel $channel): Builder
    {
        if (!$channel) {
            return Assignment::query()->query(Assignment::query()->where('channel_id', -1));
        }

        return Assignment::query()
            ->where('channel_id', $channel->getKey())
            ->with(['video.clips.user', 'downloads']);
    }

    private function emptyStateDescription(Page $page): string
    {
        return match ($page->activeTab) {
            'downloaded' => __('my_offers.messages.no_videos_downloaded'),
            'expired' => __('my_offers.messages.no_expired_offers'),
            'returned' => __('my_offers.messages.no_returned_offers'),
            default => __('my_offers.table.empty_state.description'),
        };
    }
}
