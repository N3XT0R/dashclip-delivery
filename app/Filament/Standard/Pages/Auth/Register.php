<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\Auth;

use App\Enum\Users\RoleEnum;
use App\Models\User;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
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
                $this->getTosComponent(),
            ]);
    }

    protected function getTosComponent(): Checkbox
    {
        $tosUrl = route('tos');
        $tosText = __('auth.register.tos_link_text');

        $label = __('auth.register.accept_terms_label', [
            'tos_link' => '<a href="'.$tosUrl.'" target="_blank" class="underline text-primary-600 hover:text-primary-700">'.$tosText.'</a>',
        ]);

        return Checkbox::make('accept_terms')
            ->label(fn() => new HtmlString($label))
            ->required()
            ->accepted()
            ->columnSpanFull();
    }


    protected function handleRegistration(array $data): User
    {
        if (!($data['accept_terms'] ?? false)) {
            throw ValidationException::withMessages([
                'accept_terms' => __('auth.register.accept_terms_error'),
            ]);
        }

        unset($data['accept_terms']);

        /** @var User $user */
        $user = parent::handleRegistration($data);
        $user->assignRole(RoleEnum::REGULAR->value, Filament::getCurrentPanel()?->getAuthGuard());

        return $user;
    }
}