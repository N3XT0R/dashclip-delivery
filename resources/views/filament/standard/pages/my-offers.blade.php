<x-filament-panels::page>
    <div class="mb-6">
        <div class="flex flex-wrap gap-2">
            <x-filament::tabs>
                <x-filament::tabs.item wire:click="$set('tab', 'available')" :active="$tab === 'available'">
                    {{ __('filament.my_offers.tabs.available') }}
                </x-filament::tabs.item>
                <x-filament::tabs.item wire:click="$set('tab', 'downloaded')" :active="$tab === 'downloaded'">
                    {{ __('filament.my_offers.tabs.downloaded') }}
                </x-filament::tabs.item>
                <x-filament::tabs.item wire:click="$set('tab', 'expired')" :active="$tab === 'expired'">
                    {{ __('filament.my_offers.tabs.expired') }}
                </x-filament::tabs.item>
                <x-filament::tabs.item wire:click="$set('tab', 'returned')" :active="$tab === 'returned'">
                    {{ __('filament.my_offers.tabs.returned') }}
                </x-filament::tabs.item>
            </x-filament::tabs>
        </div>
    </div>

    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>
