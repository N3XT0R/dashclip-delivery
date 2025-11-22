<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    public function getTabs(): array
    {
        return collect(RoleResource::getGuardOptions())
            ->mapWithKeys(function (string $label, string $guardName) {
                return [
                    $guardName => Tab::make($label)
                        ->modifyQueryUsing(
                            fn($query) => $query->where('guard_name', $guardName)
                        ),
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
