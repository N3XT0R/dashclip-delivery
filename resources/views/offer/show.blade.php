@php
    use Illuminate\Support\Facades\Storage;use Illuminate\Support\Number;
@endphp

@extends('layouts.app')

@section('title', 'Angebot – '.$channel->name)
@section('subtitle', 'Batch #'.$batch->id)

@section('actions')
    {{-- optional --}}
@endsection

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
