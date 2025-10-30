<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Events\User\UserCreated;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;


    protected function afterCreate(): void
    {
        /**
         * @var User $record
         */
        $record = $this->record;
        event(new UserCreated($record, true));
    }
}
