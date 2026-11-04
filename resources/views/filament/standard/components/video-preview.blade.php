@php
    use App\Application\Clips\GetPreviewUrl;
    use App\Enum\ProcessingStatusEnum;

    // the preview stays as long as the file is there, a video that is only marked as deleted keeps it
    $filesRemoved = $video?->processing_status === ProcessingStatusEnum::Deleted;
    $previewUrl = $filesRemoved
        ? null
        : app(GetPreviewUrl::class)->handle($video?->clipsWithTrashed()->first());
@endphp

<div class="rounded-lg bg-gray-50 dark:bg-gray-900 p-4">
    @if ($previewUrl)
        <video
            controls
            class="w-full max-w-2xl mx-auto rounded-lg shadow-lg"
            preload="metadata"
        >
            <source src="{{ $previewUrl }}" type="video/mp4">
            {{ __('my_offers.modal.preview.not_available') }}
        </video>
    @else
        <div class="flex items-center justify-center h-48 text-gray-500 dark:text-gray-400">
            <div class="text-center">
                <x-heroicon-o-video-camera class="w-12 h-12 mx-auto mb-2 opacity-50"/>
                <p>{{ __('my_offers.modal.preview.not_available') }}</p>
            </div>
        </div>
    @endif
</div>
