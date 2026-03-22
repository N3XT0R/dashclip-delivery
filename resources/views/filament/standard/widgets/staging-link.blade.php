<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-4">
            <div class="flex-1">
                <h2 class="text-base font-semibold">
                    Nächste Version testen
                </h2>

                <p class="text-sm text-gray-600">
                    Teste neue Funktionen vor dem Release im Testsystem und gib Feedback zur kommenden Version.
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    Deine Tests haben keinen Einfluss auf den produktiven Ablauf.
                </p>
                <div class="mt-2 flex items-center gap-4 text-sm">
                    @if (config('services.staging.changelog'))
                        <a
                            href="{{ config('services.staging.changelog') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center text-primary-600 hover:underline"
                        >
                            Änderungen ansehen
                            <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4 ml-1"/>
                        </a>
                    @endif

                    <a
                        href="https://github.com/N3XT0R/dashclip-delivery/issues"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center text-primary-600 hover:underline"
                    >
                        Feedback geben
                        <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4 ml-1"/>
                    </a>
                </div>
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
