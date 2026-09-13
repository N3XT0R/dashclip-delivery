@php use Illuminate\Support\Number; @endphp
@php($video = $assignment->video)
<article class="card min-w-0">
    <div class="mb-4 flex items-start gap-3">
        <input id="assignment-{{ $assignment->id }}" type="checkbox" name="assignment_ids[]" value="{{ $assignment->id }}" class="pickbox mt-1 size-5 shrink-0 accent-orange-700" @disabled($disabled)>
        <label for="assignment-{{ $assignment->id }}" class="file-name min-w-0 cursor-pointer font-semibold break-words">{{ $video->original_name ?: basename($video->path) }}</label>
    </div>
    <video class="aspect-video w-full rounded-lg bg-ink" src="{{ $video->preview_url ?: $assignment->temp_url }}" width="640" height="360" preload="metadata" controls playsinline aria-label="Vorschau: {{ $video->original_name ?: basename($video->path) }}"></video>
    <p class="mt-3 text-sm text-muted">{{ Number::fileSize($video->bytes) }}</p>
    @foreach ($video->clips as $clip)
        <div class="mt-4 space-y-2 text-sm text-muted">
            @if ($clip->role)<p class="font-semibold">{{ $clip->role }}</p>@endif
            @if ($clip->start_sec !== null || $clip->end_sec !== null)
                <p>{{ $clip->start_sec !== null ? gmdate('i:s', $clip->start_sec) : '' }} bis {{ $clip->end_sec !== null ? gmdate('i:s', $clip->end_sec) : '' }}</p>
            @endif
            @if ($clip->submitted_by)<p class="chip">Einsender: {{ $clip->submitted_by }}</p>@endif
            @if ($clip->note)<p class="break-words">{{ $clip->note }}</p>@endif
        </div>
    @endforeach
    <div class="mt-5 flex flex-wrap gap-3">
        @if ($disabled)
            <button type="button" class="btn" disabled>Bereits geladen</button>
        @else
            <button type="button" class="btn single-download" data-assignment-id="{{ $assignment->id }}">Einzeln laden</button>
            <a class="btn" href="{{ $assignment->temp_url }}">Direkter Download</a>
        @endif
    </div>
</article>
