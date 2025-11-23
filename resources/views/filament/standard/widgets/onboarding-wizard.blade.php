<x-filament-widgets::widget>
    <x-filament::modal
            id="onboarding-wizard" visible="{{ true }}" alignment="center" width="3xl"
            footerActionsAlignment="right"
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
            Willkommen! Lass uns dein Profil einrichten.
        </x-slot>

        <form wire:submit.prevent="submit">
            {{ $this->schema ?? $this->form }}
        </form>

        <x-slot name="footer">
            <div style="text-align: center;">
                <x-filament::button type="submit" wire:click="submit" color="primary">
                    Abschließen
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
</x-filament-widgets::widget>
