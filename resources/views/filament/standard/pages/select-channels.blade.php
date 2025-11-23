<x-filament-panels::page>
    <div class="space-y-6">

        {{ $this->form }}

        <x-filament::button wire:click="save">
            Speichern
        </x-filament::button>

        <hr class="my-6">

        {{ $this->table }}

    </div>
</x-filament-panels::page>
