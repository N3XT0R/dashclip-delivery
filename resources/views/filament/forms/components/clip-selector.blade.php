<div x-data="clipSelector()" class="space-y-2" x-init="init()">
    <input type="hidden" x-model="start" wire:model.defer="{{ $getStatePath() }}.start_sec">
    <input type="hidden" x-model="end" wire:model.defer="{{ $getStatePath() }}.end_sec">
    <input type="hidden" x-model="duration" wire:model.defer="{{ $getStatePath() }}.duration">
</div>
