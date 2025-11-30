<x-filament-panels::page>
    <div class="mx-auto max-w-4xl">
        <form wire:submit.prevent="submit" class="space-y-6">
            {{ $this->form }}
        </form>
    </div>
</x-filament-panels::page>
