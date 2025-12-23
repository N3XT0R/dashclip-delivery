<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers;

use App\Models\Assignment;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

final class MyOffersTabs
{
    /**
     * @return array<string, Tab>
     */
    public static function make(): array
    {
        return [
            'available' => Tab::make(__('my_offers.tabs.available'))
                ->modifyQueryUsing(
                    fn(Builder $query) => self::available($query)
                ),

            'downloaded' => Tab::make(__('my_offers.tabs.downloaded'))
                ->modifyQueryUsing(
                    fn(Builder $query) => self::downloaded($query)
                ),

            'expired' => Tab::make(__('my_offers.tabs.expired'))
                ->modifyQueryUsing(
                    fn(Builder $query) => self::expired($query)
                ),

            'returned' => Tab::make(__('my_offers.tabs.returned'))
                ->modifyQueryUsing(
                    fn(Builder $query) => self::returned($query)
                ),
        ];
    }

    /**
     * @param Builder<Assignment> $query
     */
    private static function available(Builder $query): Builder
    {
        return $query->available();
    }

    /**
     * @param Builder<Assignment> $query
     */
    private static function downloaded(Builder $query): Builder
    {
        return $query->downloaded();
    }

    /**
     * @param Builder<Assignment> $query
     */
    private static function expired(Builder $query): Builder
    {
        return $query->expired();
    }

    /**
     * @param Builder<Assignment> $query
     */
    private static function returned(Builder $query): Builder
    {
        return $query->returned();
    }
}
