<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Events\User\UserCreated;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;


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
                    ->required()
                    ->label('Passwort')
                    ->helperText('Wenn du nichts angibst, wird ein zufälliges Passwort generiert.'),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\Select::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload(),
            ]);
    }


    protected function afterCreate(): void
    {
        /**
         * @var User $record
         */
        $record = $this->record;
        event(new UserCreated($record, true));
    }
}
