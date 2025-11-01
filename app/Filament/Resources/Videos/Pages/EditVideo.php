<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Enum\Users\RoleEnum;
use App\Filament\Resources\Videos\VideoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVideo extends EditRecord
{
    protected static string $resource = VideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->hasRole(RoleEnum::SUPER_ADMIN->value);
    }
}
