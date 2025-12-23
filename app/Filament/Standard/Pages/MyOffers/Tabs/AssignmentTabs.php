<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers\Tabs;

use App\Models\Assignment;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

final class AssignmentTabs
{
    /**
     * @return array<string, Tab>
     */
    public function make(): array
    {
        return [
            'available' => Tab::make(__('my_offers.tabs.available'))
                ->modifyQueryUsing(
                    fn(Builder $query): Builder => $this->available($query)
                ),

            'downloaded' => Tab::make(__('my_offers.tabs.downloaded'))
                ->modifyQueryUsing(
                    fn(Builder $query): Builder => $this->downloaded($query)
                ),

            'expired' => Tab::make(__('my_offers.tabs.expired'))
                ->modifyQueryUsing(
                    fn(Builder $query): Builder => $this->expired($query)
                ),

            'returned' => Tab::make(__('my_offers.tabs.returned'))
                ->modifyQueryUsing(
                    fn(Builder $query): Builder => $this->returned($query)
                ),
        ];
    }

    /**
     * @param Builder<Assignment> $query
     */
    private function available(Builder $query): Builder
    {
        $this->guard($query);

        return $query->available();
    }

    /**
     * @param Builder<Assignment> $query
     */
    private function downloaded(Builder $query): Builder
    {
        $this->guard($query);

        return $query->downloaded();
    }

    /**
     * @param Builder<Assignment> $query
     */
    private function expired(Builder $query): Builder
    {
        $this->guard($query);

        return $query->expired();
    }

    /**
     * @param Builder<Assignment> $query
     */
    private function returned(Builder $query): Builder
    {
        $this->guard($query);

        return $query->returned();
    }

    private function guard(Builder $query): void
    {
        if (!$query->getModel() instanceof Assignment) {
            throw new LogicException(
                self::class . ' is restricted to Assignment queries in MyOffers context.'
            );
        }
    }
}
