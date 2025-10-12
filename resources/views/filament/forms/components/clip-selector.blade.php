<div x-data="clipSelector()" class="space-y-2" x-init="init()">
    <video x-ref="video" x-show="showPlayer" x-cloak controls class="w-full"></video>

    <input type="hidden" x-model="start" wire:model.defer="{{ $getStatePath() }}.start_sec">
    <input type="hidden" x-model="end" wire:model.defer="{{ $getStatePath() }}.end_sec">

    <div x-ref="slider" class="mt-2 w-full h-2 bg-gray-200 rounded"></div>

    <div class="text-sm">
        Start: <span x-text="start"></span>s – Ende: <span x-text="end"></span>s
    </div>
</div>
