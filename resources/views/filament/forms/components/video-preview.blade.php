@php
    use App\Enum\ProcessingStatusEnum;
    $video = $record->video ?? $record;
    $clip = $video?->clips()->first();
    $previewUrl = $clip->preview_path;

    $shouldPoll = !$previewUrl
        && $video?->processing_status !== ProcessingStatusEnum::Completed
        && $video?->processing_status !== ProcessingStatusEnum::Failed;
@endphp

<div @if($shouldPoll) wire:poll.5s @endif>
    @if ($previewUrl)
        <video controls width="100%">
            <source src="{{ $previewUrl }}" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    @else
        <img
            src="{{ asset('images/status/no_preview.jpg') }}"
            alt="{{ __('general.messages.no_preview_available') }}"
            class="rounded"
        >
    @endif
</div>
