<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enum\Users\RoleEnum;
use App\Events\User\UserCreated;
use App\Filament\Resources\UserResource;
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
                    ->label('Email address')
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->label('Passwort')
                    ->helperText('Wenn du nichts angibst, wird ein zufälliges Passwort generiert.'),
                Forms\Components\Select::make('roles')
                    ->label('Roles')
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

        if (empty($data['roles'])) {
            $data['roles'] = [RoleEnum::REGULAR->value];
        }

        return $data;
    }


    protected function afterCreate(): void
    {
        /**
         * @var User $record
         */
        $record = $this->record;
        event(new UserCreated(
            user: $record,
            fromBackend: true,
            plainPassword: $this->plainPassword ?? null
        ));
    }
}
