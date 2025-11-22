<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Panel;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public Collection $permissions;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public static function getPanelNames(): array
    {
        return collect(Filament::getPanels())
            ->mapWithKeys(fn(Panel $panel) => [
                $panel->getId() => ucfirst($panel->getId()),
            ])
            ->toArray();
    }

    public function form(Schema $schema): Schema
    {
        $form = parent::form($schema);
        $form->getComponent('name')?->disabled();
        $form->getComponent('guard_name')?->disabled();
        $form->schema([
            Select::make('panelSwitcher')
                ->label('Panel wählen')
                ->options(static::getPanelNames())
                ->default(fn($record) => $record->panel ?? null)
                ->reactive()
                ->dehydrated(false),
            ...parent::form($form)->getComponents(),
        ]);
        return $form;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->permissions = collect($data)
            ->filter(fn(mixed $permission, string $key): bool => !in_array($key,
                ['name', 'guard_name', 'select_all', Utils::getTenantModelForeignKey()], true))
            ->values()
            ->flatten()
            ->unique();

        if (Utils::isTenancyEnabled() && Arr::has($data,
                Utils::getTenantModelForeignKey()) && filled($data[Utils::getTenantModelForeignKey()])) {
            return Arr::only($data, ['name', 'guard_name', Utils::getTenantModelForeignKey()]);
        }

        return Arr::only($data, ['name', 'guard_name']);
    }

    protected function afterSave(): void
    {
        $permissionModels = collect();
        $this->permissions->each(function (string $permission) use ($permissionModels): void {
            $permissionModels->push(Utils::getPermissionModel()::firstOrCreate([
                'name' => $permission,
                'guard_name' => $this->data['guard_name'],
            ]));
        });

        // @phpstan-ignore-next-line
        $this->record->syncPermissions($permissionModels);
    }
}
