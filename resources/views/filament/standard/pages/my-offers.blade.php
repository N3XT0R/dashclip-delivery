<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::tabs>
            @foreach ($this->getTabs() as $key => $tab)
                <x-filament::tabs.item
                        :active="$activeTab === $key"
                        wire:click="$set('activeTab', '{{ $key }}')"
                >
                    {{ $tab->getLabel() }}
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>

        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
