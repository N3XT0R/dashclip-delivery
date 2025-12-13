<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">{{ __('filament.my_offers.tabs.available') }}</x-slot>
            {{ $this->table }}
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">{{ __('filament.my_offers.tabs.downloaded') }}</x-slot>
            <p class="text-sm text-gray-500">{{ __('filament.my_offers.placeholders.downloaded') }}</p>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">{{ __('filament.my_offers.tabs.expired') }}</x-slot>
            <p class="text-sm text-gray-500">{{ __('filament.my_offers.placeholders.expired') }}</p>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">{{ __('filament.my_offers.tabs.returned') }}</x-slot>
            <p class="text-sm text-gray-500">{{ __('filament.my_offers.placeholders.returned') }}</p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
