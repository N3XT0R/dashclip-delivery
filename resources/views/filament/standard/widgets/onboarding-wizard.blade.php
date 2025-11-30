<x-filament-widgets::widget>
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="space-y-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Willkommen im Panel 👋</h2>
                <p class="text-sm text-gray-600">Starte hier dein Onboarding, um alle Funktionen des DashClip Panels kennenzulernen.</p>
            </div>
            <x-filament::button tag="a" href="{{ route('filament.standard.pages.onboarding-wizard') }}" color="primary">
                Onboarding starten
            </x-filament::button>
        </div>
    </div>
</x-filament-widgets::widget>
