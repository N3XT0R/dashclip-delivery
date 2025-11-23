<x-filament::modal id="onboarding-wizard" width="3xl" alignment="center">
    <x-slot name="heading">
        Willkommen! Lass uns dein Profil einrichten.
    </x-slot>

    <x-slot name="description">
        Bitte führe die folgenden Schritte durch, um dein Konto vollständig zu aktivieren.
    </x-slot>

    <form wire:submit.prevent="submit">
        {{-- Hier dein Wizard oder Schema/Form --}}
        {{ $this->schema ?? $this->form }}
    </form>

    <x-slot name="footer">
        <x-filament::button type="submit" form="your-form-id">
            Abschließen
        </x-filament::button>
    </x-slot>
</x-filament::modal>
