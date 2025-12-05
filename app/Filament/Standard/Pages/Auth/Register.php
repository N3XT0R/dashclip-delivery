<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\Auth;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\User;
use App\Repository\RoleRepository;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
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
        $data['terms_accepted_at'] = now();

        /** @var User $user */
        $user = parent::handleRegistration($data);
        $roleRepository = app(RoleRepository::class);

        try {
            $user->assignRole($roleRepository->getRoleByRoleEnum(
                RoleEnum::REGULAR,
                Filament::getCurrentPanel()?->getAuthGuard()
            ));
        } catch (\Throwable $e) {
            Log::error('Role assignment failed', ['exception' => $e, 'user' => $user]);
            $user->delete();


            $this->getRoleAssignmentFailedNotification(
                RoleEnum::REGULAR,
                GuardEnum::DEFAULT
            )->send();
        }

        return $user;
    }

    protected function getRoleAssignmentFailedNotification(RoleEnum $role, ?GuardEnum $guard = null): Notification
    {
        return Notification::make()
            ->title(__('auth.register.role_assignment_failed_title'))
            ->body(__('auth.register.role_assignment_failed_body', [
                'role' => $role,
                'guard' => $guard?->value ?: 'web',
            ]))
            ->danger();
    }

}