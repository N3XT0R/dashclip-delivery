@php
    $video = $record->video ?? $record;
@endphp

@if ($video?->preview_url)
    <video controls width="100%">
        <source src="{{$video->preview_url }}" type="video/mp4">
        Your browser does not support the video tag.
    </video>
@else
    <p>{{__('general.messages.no_preview_available')}}</p>
@endif
