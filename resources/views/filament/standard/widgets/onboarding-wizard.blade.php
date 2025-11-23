<x-filament-widgets::widget>
    <x-filament::modal
            id="onboarding-wizard" visible="{{ true }}" alignment="center" width="3xl"
    >
        <x-slot name="trigger">
            <button
                    x-data
                    x-init="$nextTick(() => $el.click())"
                    class="hidden"
                    type="button"
            >
                Auto-open
            </button>
        </x-slot>
        <x-slot name="heading">
            Willkommen! So funktioniert DashClip:
        </x-slot>

        <form wire:submit.prevent="submit">
            {{ $this->schema ?? $this->form }}
        </form>

        <x-slot name="footer">
            <x-filament::button type="submit" wire:click="submit" color="primary" size="xs">
                Nicht mehr anzeigen und abschließen
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-widgets::widget>
