<x-filament-panels::page.simple :class="blank($this->userUndertakingMultiFactorAuthentication) ? 'dc-login-initial' : 'dc-login-challenge'">
    {{ $this->content }}
</x-filament-panels::page.simple>
