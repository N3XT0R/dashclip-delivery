<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-4">
            <div class="flex-1">
                <h2 class="text-base font-semibold">
                    Nächste Version testen
                </h2>

                <p class="text-sm text-gray-600">
                    Hier kannst du das Testsystem öffnen und neue Funktionen vorab ausprobieren.
                </p>

                @if (config('services.staging.changelog'))
                    <a
                        href="{{ config('services.staging.changelog') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-2 inline-flex items-center text-sm text-primary-600 hover:underline"
                    >
                        Änderungen ansehen
                        <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4 ml-1"/>
                    </a>
                @endif
            </div>

            <div class="shrink-0">
                <x-filament::button
                    tag="a"
                    :href="config('services.staging.url')"
                    target="_blank"
                    rel="noopener noreferrer"
                    icon="heroicon-o-arrow-top-right-on-square"
                    size="lg"
                >
                    Testsystem öffnen
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
