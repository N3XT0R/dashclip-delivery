<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers;

use App\Models\Assignment;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

final class MyOffersTabs
{
    public function make(): array
    {
        return [
            'available' => Tab::make(__('my_offers.tabs.available'))
                ->modifyQueryUsing(fn(Builder $q) => $this->available($q)),

            'downloaded' => Tab::make(__('my_offers.tabs.downloaded'))
                ->modifyQueryUsing(fn(Builder $q) => $this->downloaded($q)),

            'expired' => Tab::make(__('my_offers.tabs.expired'))
                ->modifyQueryUsing(fn(Builder $q) => $this->expired($q)),

            'returned' => Tab::make(__('my_offers.tabs.returned'))
                ->modifyQueryUsing(fn(Builder $q) => $this->returned($q)),
        ];
    }

    /**
     * @param Builder<Assignment> $query
     */
    private function available(Builder $query): Builder
    {
        return $query->available();
    }

    /**
     * @param Builder<Assignment> $query
     */
    private function downloaded(Builder $query): Builder
    {
        return $query->downloaded();
    }

    /**
     * @param Builder<Assignment> $query
     */
    private function expired(Builder $query): Builder
    {
        return $query->expired();
    }

    /**
     * @param Builder<Assignment> $query
     */
    private function returned(Builder $query): Builder
    {
        return $query->returned();
    }
}
