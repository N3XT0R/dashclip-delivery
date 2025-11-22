<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;


    public static function getPanelNames(): array
    {
        return collect(Filament::getPanels())
            ->mapWithKeys(fn(Panel $panel) => [
                $panel->getId() => ucfirst($panel->getId()),
            ])
            ->toArray();
    }

    public function getTabs(): array
    {
        return collect(Filament::getPanels())
            ->mapWithKeys(function (Panel $panel) {
                $authGuard = $panel->getAuthGuard();

                return [
                    $authGuard => Tab::make(ucfirst($authGuard))
                        ->modifyQueryUsing(
                            fn($query) => $query->where('guard_name', $authGuard)
                        )
                ];
            })
            ->toArray();
    }

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
