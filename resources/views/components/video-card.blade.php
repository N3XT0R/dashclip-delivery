@php use Illuminate\Support\Number; @endphp
@php($v = $assignment->video)

<div class="card" @if($disabled) style="opacity:0.6; pointer-events:none;" @endif>
    <label class="flex items-start gap-3 cursor-pointer w-full">
        @if($disabled === false)
            <input type="checkbox"
                   name="assignment_ids[]"
                   value="{{ $assignment->id }}"
                   class="pickbox mt-2 shrink-0">
        @endif

        <div class="flex-1 min-w-0">
            {{-- Dateiname --}}
            <div class="file-name font-semibold mb-2 break-words line-clamp-2">
                {{ $v->original_name ?: basename($v->path) }}
            </div>

            {{-- Video --}}
            <video class="thumb w-full rounded-lg bg-[#0e1116]"
                   src="{{ $v->preview_url ?: $assignment->temp_url }}"
                   preload="metadata"
                   controls playsinline></video>

            {{-- Dateigröße --}}
            <div class="muted mt-2">
                {{ Number::fileSize($v->bytes) }}
            </div>

            {{-- Clips --}}
            @foreach($v->clips as $clip)
                <div class="mt-3 space-y-1 text-sm muted">
                    {{-- Erste Zeile: Role + Time + Einsender nebeneinander --}}
                    <div class="flex flex-wrap gap-x-3 gap-y-1 items-center">
                        <div>
                            @if($clip->role)
                                <span><strong>{{ $clip->role }}:</strong></span>
                            @endif
                        </div>
                        <div>
                            @if(!is_null($clip->start_sec) || !is_null($clip->end_sec))
                                <span>
                                {{ $clip->start_sec !== null ? gmdate('i:s',$clip->start_sec) : '' }}
                                –
                                {{ $clip->end_sec !== null ? gmdate('i:s',$clip->end_sec) : '' }}
                            </span>
                            @endif
                        </div>
                    </div>
                    @if($clip->submitted_by)
                        <div class="mt-1">
                            <span class="chip">Einsender: {{ $clip->submitted_by }}</span>
                        </div>
                    @endif

                    {{-- Zweite Zeile: Note immer drunter --}}
                    @if($clip->note)
                        <div class="mt-1 text-sm muted">{{ $clip->note }}</div>
                    @endif
                </div>
            @endforeach

            {{-- Buttons --}}
            <div class="mt-3 flex gap-2 flex-wrap">
                @if($disabled)
                    <span class="btn btn-sm disabled">Bereits geladen</span>
                @else
                    <a class="btn btn-sm" href="{{ $assignment->temp_url }}">Einzeln laden</a>
                @endif

                <button type="button" class="btn btn-sm"
                        onclick="this.closest('.card').querySelector('.inline-preview').style.display='block'">
                    Vorschau öffnen
                </button>
            </div>

            {{-- Inline Preview --}}
            <div class="inline-preview hidden mt-2">
                <video controls preload="metadata" class="w-full rounded-lg">
                    <source src="{{ $v->preview_url ?: $assignment->temp_url }}" type="video/mp4"/>
                    Dein Browser unterstützt das Video-Tag nicht.
                </video>
            </div>
        </div>
    </label>
</div>
