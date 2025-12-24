@php
    use Illuminate\Support\Facades\Storage;use Illuminate\Support\Number;
@endphp

@extends('layouts.app')

@section('title', 'Angebot – '.$channel->name)
@section('subtitle', 'Batch #'.$batch->id)

@section('actions')
    {{-- optional --}}
@endsection
@push('styles')
    <style>
        .register-drawer {
            position: fixed;
            right: 0;
            top: 35%;
            display: flex;
            align-items: stretch;

            transform: translateX(260px);
            transition: transform .25s ease;
            z-index: 50;

            background: #fff;
            box-shadow: -8px 0 24px rgba(0, 0, 0, .12);
            border-radius: 8px 0 0 8px;
        }

        .register-drawer.open {
            transform: translateX(0);
        }


        .drawer-handle {
            position: absolute;
            left: -44px;
            width: 44px;
            height: 160px;

            transform: rotate(-90deg);
            transform-origin: top left;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px 6px 0 0;
            box-shadow: 0 -4px 10px rgba(0, 0, 0, .08);
        }

        .drawer-content {
            width: 260px;
            padding: 16px;
            background: inherit;
        }

        .drawer-handle {
            position: absolute;
            left: -120px;
            width: 120px;
        }

        .drawer-handle:hover {
            background: #f0f2f6;
        }

        .drawer-content {
            width: 260px;
            padding: 16px;
        }
    </style>
@endpush
@section('content')
    @php
        // nach bundle_key gruppieren (Fallback "Einzeln")
        $byBundle = $items->groupBy(function($a){
          $firstClip = optional($a->video->clips->first());
          return ($firstClip && $firstClip->bundle_key) ? $firstClip->bundle_key : 'Einzeln';
        });
    @endphp

    @if ($errors->any())
        <div class="panel flash--err" style="margin-bottom:16px;">
            <strong>Es gab ein Problem:</strong>
            <ul style="margin:6px 0 0 18px;">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($items->isEmpty())
        <div class="panel">Für diesen Batch sind keine Videos verfügbar.</div>
    @else
        <form method="POST" action="{{ $zipPostUrl }}" id="zipForm" data-zip-post-url="{{ $zipPostUrl }}">
            @csrf

            @foreach($byBundle as $bundle => $group)
                <h3 style="margin:18px 2px;">Gruppe: {{ $bundle }}</h3>
                <div class="grid">
                    @foreach($group as $assignment)
                        <x-video-card :assignment="$assignment"/>
                    @endforeach
                </div>
            @endforeach

            <div style="display:flex; gap:10px; margin-top:16px;">
                <button type="button" class="btn" id="selectAll">Alle auswählen</button>
                <button type="button" class="btn" id="selectNone">Alle abwählen</button>
                <button type="button" class="btn" id="zipSubmit">Auswahl als ZIP herunterladen</button>
                <span class="muted" id="selCount" style="align-self:center;">0 ausgewählt</span>
            </div>
        </form>
    @endif
    <hr class="muted-separator">
    <div id="registerDrawer" class="register-drawer">
        <button class="drawer-handle" type="button" onclick="toggleRegisterDrawer()">
            Zugriff erweitern
        </button>

        <div class="drawer-content">
            <h3>Mehr Zugriff erhalten</h3>
            <p class="muted">
                Registriere dich, um auf weitere Inhalte und Downloads zuzugreifen.
            </p>

            <a href="{{ route('filament.standard.auth.register') }}" class="btn primary">
                Jetzt registrieren
            </a>

            <a href="{{ route('filament.standard.auth.login') }}" class="btn subtle">
                Ich habe bereits ein Konto
            </a>
        </div>
    </div>
    @if($pickedUp->isNotEmpty())
        <h2 style="margin-bottom:12px;">Bereits heruntergeladen</h2>

        @php
            $byBundlePicked = $pickedUp->groupBy(function($a){
              $firstClip = optional($a->video->clips->first());
              return ($firstClip && $firstClip->bundle_key) ? $firstClip->bundle_key : 'Einzeln';
            });
        @endphp

        @foreach($byBundlePicked as $bundle => $group)
            <h3 style="margin:18px 2px;">Gruppe: {{ $bundle }}</h3>
            <div class="grid">
                @foreach($group as $assignment)
                    <x-video-card :assignment="$assignment" :disabled="true"/>
                @endforeach
            </div>
        @endforeach
    @endif
@endsection
<script>
    function toggleRegisterDrawer() {
        document.getElementById('registerDrawer')
            .classList.toggle('open');
    }
</script>
