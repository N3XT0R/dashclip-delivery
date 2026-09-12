@extends('layouts.app')
@section('title', ($title ?? 'Fehler').' | DashClip Delivery')
@section('description', 'Diese Seite ist gerade nicht verfügbar. Kehre zur Startseite zurück oder melde dich in deinem Konto an.')
@section('robots', 'noindex, nofollow')
@section('content')
    <section class="mx-auto max-w-2xl rounded-2xl border border-border bg-panel p-8 sm:p-12">
        <p class="mb-4 text-sm font-bold tracking-widest text-orange-700 dark:text-orange-400">{{ $code ?? 'Fehler' }}</p>
        <h1 class="text-3xl font-bold">{{ $title ?? 'Diese Seite ist nicht verfügbar' }}</h1>
        @isset($message)<p class="mt-5 text-muted">{{ $message }}</p>@endisset
        <div class="mt-8 flex flex-wrap gap-3">
            <x-public.button :href="url('/')">Zur Startseite</x-public.button>
            <x-public.button :href="route('filament.standard.auth.login')" variant="secondary">Anmelden</x-public.button>
        </div>
        @if (config('app.debug') && isset($exception))
            <details class="mt-8">
                <summary>Debug-Info</summary>
                <pre class="mt-4 overflow-x-auto rounded-lg bg-bg p-4 text-xs">{{ get_class($exception) }}
{{ $exception->getMessage() }}
{{ $exception->getTraceAsString() }}</pre>
            </details>
        @endif
    </section>
@endsection
