<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="space-y-2">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                    Willkommen! So funktioniert DashClip
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Durchlaufe unser kurzes Onboarding, um einen Überblick über Upload, Kanal-Auswahl und Videomanagement zu erhalten.
                </p>
            </div>

            <x-filament::button
                tag="a"
                color="primary"
                href="{{ route('filament.standard.pages.onboarding', ['tenant' => \Filament\Facades\Filament::getTenant()?->getKey()]) }}"
            >
                Onboarding starten
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
