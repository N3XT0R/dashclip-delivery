@php use App\Enum\Users\RoleEnum; @endphp
@extends('layouts.app')
@section('title', 'Angebot | '.$channel->name)
@section('description', 'Deine angebotenen Clips ansehen, auswählen und herunterladen.')
@section('robots', 'noindex, nofollow')
@section('subtitle', 'Batch #'.$batch->id)
@section('page_assets')
    @vite('resources/js/offers.js')
@endsection
@section('content')
    <h1 class="mb-8 text-3xl font-bold">Angebot für {{ $channel->name }}</h1>
    @if (null === auth()->user() || ! auth()->user()?->hasRole(RoleEnum::CHANNEL_OPERATOR->value))
        <aside class="mb-10 rounded-xl border border-border bg-panel p-6">
            <h2 class="text-lg font-semibold">Hinweis: Portalzugang wird künftig vorausgesetzt</h2>
            <p class="mt-3 max-w-3xl text-muted">Künftig erfolgt der Zugriff zentral über ein Benutzerkonto im Portal. Dort findest du deine Angebote, Downloads und ihren Status. Bestehende Direktzugriffe funktionieren während der Übergangsphase weiterhin.</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <x-public.button :href="route('filament.standard.auth.register')">Jetzt registrieren</x-public.button>
                <x-public.button :href="route('filament.standard.auth.login')" variant="secondary">Login</x-public.button>
            </div>
        </aside>
    @endif
    @if ($items->isEmpty())
        <p class="panel">Für diesen Batch sind keine Videos verfügbar.</p>
    @else
        <noscript><p class="flash">Die Auswahl als ZIP benötigt JavaScript. Einzelne Clips kannst du über ihren direkten Download-Link herunterladen.</p></noscript>
        <form method="POST" action="{{ $zipPostUrl }}" id="zipForm" data-zip-post-url="{{ $zipPostUrl }}">
            @csrf
            @foreach ($items->groupBy(fn ($assignment) => $assignment->video->clips->first()?->bundle_key ?: 'Einzeln') as $bundle => $group)
                <h2 class="mt-8 mb-5 text-xl font-semibold">Gruppe: {{ $bundle }}</h2>
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($group as $assignment)<x-video-card :assignment="$assignment" />@endforeach
                </div>
            @endforeach
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <x-public.button variant="secondary" id="selectAll">Alle auswählen</x-public.button>
                <x-public.button variant="secondary" id="selectNone">Alle abwählen</x-public.button>
                <x-public.button id="zipSubmit">Auswahl als ZIP herunterladen</x-public.button>
                <span class="text-sm text-muted" id="selCount" role="status" aria-live="polite">0 ausgewählt</span>
            </div>
        </form>
    @endif
    @if ($pickedUp->isNotEmpty())
        <h2 class="mt-12 mb-6 border-t border-border pt-10 text-2xl font-bold">Bereits heruntergeladen</h2>
        @foreach ($pickedUp->groupBy(fn ($assignment) => $assignment->video->clips->first()?->bundle_key ?: 'Einzeln') as $bundle => $group)
            <h3 class="mt-8 mb-5 text-xl font-semibold">Gruppe: {{ $bundle }}</h3>
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($group as $assignment)<x-video-card :assignment="$assignment" :disabled="true" />@endforeach
            </div>
        @endforeach
    @endif
@endsection
