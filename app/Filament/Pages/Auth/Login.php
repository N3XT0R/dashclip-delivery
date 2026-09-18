<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use App\Exceptions\Passkey\PasskeyException;
use App\Filament\Forms\Components\PasskeyField;
use App\Services\Passkeys\CeremonyService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    protected static string $layout = 'filament.layouts.login';

    /** @return array<Action> */
    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            Action::make('passkeyLogin')->label(__('passkeys.login'))->color('gray')
                ->icon(Heroicon::OutlinedKey)
                ->modalDescription(__('passkeys.login_description'))
                ->schema([PasskeyField::make('assertion')->ceremony('login', null)])
                ->action(function (array $data, Schema $schema): void {
                    try {
                        $user = app(CeremonyService::class)->authenticate(null, Filament::getAuthGuard(), $data['assertion']);
                        if (! $this->isUserAllowedToAccessPanel($user)) {
                            throw new PasskeyException(__('passkeys.invalid'));
                        }
                    } catch (PasskeyException $exception) {
                        throw ValidationException::withMessages([$schema->getStatePath().'.assertion' => $exception->getMessage()]);
                    }

                    Filament::auth()->login($user);
                    session()->regenerate();
                    $this->redirectIntended(Filament::getUrl());
                })->rateLimit(5),
        ];
    }

    public function getHeading(): string|Htmlable|null
    {
        return filled($this->userUndertakingMultiFactorAuthentication)
            ? parent::getHeading()
            : __('login.heading');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return filled($this->userUndertakingMultiFactorAuthentication)
            ? parent::getSubheading()
            : __('login.subheading');
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->prefixIcon(Heroicon::OutlinedEnvelope)
            ->placeholder(__('login.email_placeholder'));
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->prefixIcon(Heroicon::OutlinedLockClosed)
            ->placeholder(__('login.password_placeholder'));
    }
}
