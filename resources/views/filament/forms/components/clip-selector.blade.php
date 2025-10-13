@php
    $basePath = $getContainer()->getStatePath();
@endphp
<div
    x-data="clipSelector({
        startModel: @entangle("{$basePath}.start_sec").live,
        endModel: @entangle("{$basePath}.end_sec").live,
        durationModel: @entangle("{$basePath}.duration").live,
    })"
    class="space-y-3"
    x-init="init()"
    x-cloak
>
    <div class="aspect-video w-full overflow-hidden rounded-lg bg-gray-900" x-show="hasVideo">
        <video
            x-ref="video"
            class="h-full w-full object-contain"
            playsinline
            controls
        ></video>
    </div>

    <div x-ref="sliderWrapper" x-show="hasVideo" class="mt-2">
        <div x-ref="slider" class="h-1 rounded bg-gray-200"></div>
    </div>

    <div class="flex items-center justify-between text-sm text-gray-700">
        <span>
            Start: <span class="font-mono" x-text="formattedStart"></span>
        </span>
        <span>
            Ende: <span class="font-mono" x-text="formattedEnd"></span>
        </span>
    </div>
</div>
