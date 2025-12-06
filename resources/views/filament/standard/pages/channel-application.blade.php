<x-filament-panels::page>
    {{ $this->form }}
    <x-filament::button type="submit" color="primary" form="form">
        {{ __('filament.channel_application.submit') }}
    </x-filament::button>
</x-filament-panels::page>