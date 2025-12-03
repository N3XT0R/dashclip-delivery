<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Support\Facades\Auth;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        $user = Auth::user();

        if (!$user->hasRole(RoleEnum::getRoles(), GuardEnum::DEFAULT->value)) {
            activity()
                ->event('wrong_panel')
                ->causedBy($user)
                ->performedOn($user)
                ->event('login')
                ->withProperties([
                    'panel' => 'admin',
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('User logged in to admin panel');
            Auth::logout();


            $this->throwFailureValidationException();
        }

        return $response;
    }
}