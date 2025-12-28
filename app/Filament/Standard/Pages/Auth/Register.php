<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\Auth;

use App\Models\User;
use App\Support\FilamentComponents;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class Register extends BaseRegister
{

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                FilamentComponents::tosCheckbox(),
            ]);
    }


    protected function handleRegistration(array $data): User
    {
        if (!($data['accept_terms'] ?? false)) {
            throw ValidationException::withMessages([
                'accept_terms' => __('auth.register.accept_terms_error'),
            ]);
        }

        unset($data['accept_terms']);
        $data['terms_accepted_at'] = now();

        /** @var User $user */
        $user = parent::handleRegistration($data);
        return $user;
    }
}
