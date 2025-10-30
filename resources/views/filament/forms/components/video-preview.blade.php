@if ($record->video?->preview_url)
    <video controls width="100%">
        <source src="{{ $record->video->preview_url }}" type="video/mp4">
        Your browser does not support the video tag.
    </video>
@else
    <p>No preview available.</p>
@endif
