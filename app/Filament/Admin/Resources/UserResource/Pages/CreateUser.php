<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Enum\Users\RoleEnum;
use App\Events\User\UserCreated;
use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** @var string|null */
    private ?string $plainPassword = null;


    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('submitted_name'),
                Forms\Components\TextInput::make('email')
                    ->label(__('filament.admin.labels.email_address'))
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->label(__('filament.admin.labels.password'))
                    ->helperText(__('filament.admin.messages.password_helper')),
                Forms\Components\Select::make('roles')
                    ->label(__('filament.admin.labels.roles'))
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload(),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['password'])) {
            $this->plainPassword = Str::password(12);
            $data['password'] = bcrypt($this->plainPassword);
        } else {
            $this->plainPassword = $data['password'];
            $data['password'] = bcrypt($data['password']);
        }

        return $data;
    }


    protected function afterCreate(): void
    {
        /**
         * @var User $record
         */
        $record = $this->record;
        if ($record->roles()->count() === 0) {
            $record->assignRole(RoleEnum::REGULAR->value);
        }

        event(
            new UserCreated(
                user: $record,
                fromBackend: true,
                plainPassword: $this->plainPassword ?? null
            )
        );
    }
}
