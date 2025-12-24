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
        /* === Register Callout ===================================== */
        .register-callout {
            width: 75%;
            max-width: 900px;
            margin: 32px auto;

            background: #ffffff;
            border: 1px solid #e6e8ee;
            border-radius: 10px;

            padding: 16px 18px;

            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        }

        .register-callout h3 {
            margin: 0 0 6px 0;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }

        .register-callout p {
            margin: 0 0 14px 0;
            font-size: 14px;
            line-height: 1.4;
            color: #4b5563;
        }

        .register-callout-actions {
            display: flex;
            gap: 10px;
        }

        .register-callout .btn.primary {
            padding: 8px 14px;
        }

        .register-callout .btn.subtle {
            padding: 8px 14px;
        }

        .register-callout:hover {
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.08);
        }

        .register-tab {
            position: fixed;
            left: 0;
            top: 50%;
            transform: translateY(-50%);

            writing-mode: vertical-rl;
            text-orientation: mixed;

            padding: 10px 8px;

            background: #bec1c8;
            border: 1px solid #e6e8ee;
            border-left: none;
            border-radius: 0 8px 8px 0;

            font-size: 12px;
            font-weight: 600;
            letter-spacing: .08em;
            color: #374151;

            cursor: pointer;
            z-index: 40;
        }

        .register-tab:hover {
            background: #e9edf5;
        }

        .register-benefits {
            margin: 10px 0 14px 0;
            padding-left: 0;
            list-style: none;
            font-size: 13px;
            color: #374151;
        }

        .register-benefits li {
            margin-bottom: 6px;
        }

    </style>
@endpush
@section('content')
    @guest
        <div class="register-callout">
            <h3>Zentraler Zugriff über das Portal</h3>
            <p>
                Du kannst Angebote weiterhin wie gewohnt über diese Seite nutzen.
                Viele Kanalbetreiber entscheiden sich später zusätzlich für ein Benutzerkonto,
                um ihre Inhalte zentral und übersichtlich zu verwalten.
            </p>
            <ul class="register-benefits">
                <li>✔ Zentrale Übersicht über alle Angebote, Downloads und Status</li>
                <li>✔ Dauerhafter Zugriff auf aktuelle und zukünftige Inhalte</li>
                <li>✔ Mehr Sicherheit durch persönliches Benutzerkonto</li>
                <li>✔ Bestehende Zugriffe per E-Mail bleiben weiterhin möglich</li>
                <li>✔ Perspektivisch: Automatisierte Anbindung an eigene Workflows und Systeme</li>
            </ul>
            <div class="register-callout-actions">
                <a href="{{ route('filament.standard.auth.register') }}" class="btn primary">
                    Jetzt registrieren
                </a>
                <a href="{{ route('filament.standard.auth.login') }}" class="btn subtle">
                    Login
                </a>
            </div>
        </div>
    @endguest
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
    @guest
        <button
            class="register-tab"
            onclick="document.querySelector('.register-callout')?.scrollIntoView({behavior:'smooth'})"
        >
            Registrieren
        </button>
    @endguest
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
