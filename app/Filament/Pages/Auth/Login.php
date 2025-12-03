<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        $user = Auth::user();

        if (!$user->hasRole(RoleEnum::REGULAR, GuardEnum::STANDARD->value)) {
            Auth::logout();

            Notification::make()
                ->danger()
                ->title('Kein Zugriff')
                ->body('Dieses Login ist nur für Administratoren.')
                ->send();
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->event('login')
                ->withProperties([
                    'panel' => 'admin',
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('User logged in to admin panel');

            $this->throwFailureValidationException();
        }

        return $response;
    }

    protected function throwFailureValidationException(): never
    {
        activity()
            ->event('login_failed')
            ->withProperties([
                'panel' => 'admin',
                'email' => $this->data['email'] ?? null,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Failed login attempt on admin panel');

        parent::throwFailureValidationException();
    }
}