@php use Illuminate\Support\Number; @endphp
@php($v = $assignment->video)

<div class="card" @if($disabled) style="opacity:0.6; pointer-events:none;" @endif>
    <div style="flex:1;">
        <div class="file-name" style="font-weight:600; margin-bottom:6px;">
            {{ Number::fileSize($v->bytes) }}
        </div>

        <video class="thumb"
               src="{{ $v->preview_url ?: $assignment->temp_url }}"
               preload="metadata"
               style="width:100%;height:auto;border-radius:10px;background:#0e1116;"
               controls playsinline></video>

        <div class="muted" style="margin-top:6px;">
            {{ Number::fileSize($v->bytes) }}
        </div>

        @foreach($v->clips as $clip)
            <div class="muted" style="margin-top:4px; word-break: break-word;">
                @if($clip->role)
                    <strong>{{ $clip->role }}:</strong>
                @endif
                @if(!is_null($clip->start_sec))
                    {{ gmdate('i:s',$clip->start_sec) }}
                @endif
                –
                @if(!is_null($clip->end_sec))
                    {{ gmdate('i:s',$clip->end_sec) }}
                @endif
                @if($clip->note)
                    · {{ $clip->note }}
                @endif
                @if($clip->submitted_by)
                    · <span class="chip">Einsender: {{ $clip->submitted_by }}</span>
                @endif
            </div>
        @endforeach

        <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
            @if($disabled)
                <span class="btn btn-sm disabled">Bereits geladen</span>
            @else
                <a class="btn btn-sm" href="{{ $assignment->temp_url }}">Einzeln laden</a>
            @endif
            <button type="button" class="btn btn-sm"
                    onclick="this.closest('.card').querySelector('.inline-preview').style.display='block'">
                Vorschau öffnen
            </button>
            <div class="inline-preview" style="display:none; margin-top:8px;">
                <video controls preload="metadata" style="width:100%; border-radius:10px;">
                    <source src="{{ $v->preview_url ?: $a->temp_url }}" type="video/mp4"/>
                    Dein Browser unterstützt das Video-Tag nicht.
                </video>
            </div>
        </div>
    </div>
</div>
