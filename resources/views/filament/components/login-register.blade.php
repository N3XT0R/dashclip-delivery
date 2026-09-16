@if (filament()->hasRegistration())
    <div class="dc-login-register" x-show="! $wire.userUndertakingMultiFactorAuthentication">
        <p class="dc-login-divider"><span>{{ __('login.or') }}</span></p>
        <p>{{ __('login.no_account') }}</p>
        <a href="{{ filament()->getRegistrationUrl() }}" class="dc-login-register-link">{{ __('login.register') }}</a>
    </div>
@endif
