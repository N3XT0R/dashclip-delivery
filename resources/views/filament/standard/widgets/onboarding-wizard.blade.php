<x-filament-widgets::widget class="fi-account-widget">
    <x-filament::section>

        {{-- Header --}}
        <div class="fi-account-widget-main !mt-0">
            <h2 class="fi-account-widget-heading">
                Willkommen im Panel 👋
            </h2>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Starte jetzt dein Onboarding für DashClip.
            </p>
        </div>

        {{-- Modal mit LAZY loading für das Formular --}}
        <x-filament::modal
                id="onboarding-wizard"
                alignment="center"
                width="3xl"
                lazy
        >
            <x-slot name="trigger">
                <x-filament::button
                        color="primary"
                        size="sm"
                        icon="heroicon-o-sparkles"
                        type="button"
                >
                    Onboarding starten
                </x-filament::button>
            </x-slot>

            <x-slot name="heading">
                Willkommen! So funktioniert DashClip:
            </x-slot>

            {{-- WICHTIG: Das Formular lädt ERST, wenn das Modal geöffnet wird --}}
            <form wire:submit.prevent="submit">
                {{ $this->schema ?? $this->form }}
            </form>

            <x-slot name="footer">
                <x-filament::button
                        type="submit"
                        wire:click="submit"
                        color="primary"
                        size="xs"
                >
                    Nicht mehr anzeigen
                </x-filament::button>
            </x-slot>
        </x-filament::modal>

    </x-filament::section>
</x-filament-widgets::widget>
