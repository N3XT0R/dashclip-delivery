<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold">
                    Nächste Version testen
                </h2>

                <p class="text-sm text-gray-600">
                    Hier kannst du die Staging-Umgebung öffnen und die kommende Version testen.
                </p>
            </div>

            <x-filament::button
                tag="a"
                :href="config('services.staging.url')"
                target="_blank"
                rel="noopener noreferrer"
                icon="heroicon-o-arrow-top-right-on-square"
            >
                Staging öffnen
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
