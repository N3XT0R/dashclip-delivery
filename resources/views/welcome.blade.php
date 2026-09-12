@extends('layouts.app')
@section('title', 'Deine Dashcam-Clips. Mehr Reichweite. | DashClip Delivery')
@section('description', 'Lade deine Dashcam-Clips einmal hoch. DashClip Delivery verteilt sie automatisch an passende Kanäle, mit Vorschau, Downloads und nachvollziehbaren Angeboten.')
@section('full_width', '1')

@section('content')
    <section class="relative isolate overflow-hidden bg-ink text-white" aria-labelledby="hero-title">
        <picture class="absolute inset-0 -z-20">
            <source type="image/webp" srcset="{{ asset('images/marketing/hero-640.webp') }} 640w, {{ asset('images/marketing/hero-1280.webp') }} 1280w, {{ asset('images/marketing/hero-1920.webp') }} 1920w" sizes="100vw">
            <img src="{{ asset('images/marketing/hero.jpg') }}" width="1280" height="720" alt="" loading="eager" fetchpriority="high" class="h-full w-full object-cover object-center">
        </picture>
        <div class="absolute inset-0 -z-10 bg-linear-to-r from-ink/95 via-ink/75 to-ink/30"></div>
        <div class="public-width py-20 sm:py-24 lg:py-32">
            <p class="mb-6 text-sm font-semibold tracking-widest text-orange-300 uppercase">Einmal hochladen. Mehrere Kanäle erreichen.</p>
            <h1 id="hero-title" class="max-w-3xl text-4xl leading-[1.08] font-extrabold tracking-tight sm:text-5xl lg:text-6xl">Deine Dashcam-Clips.<span class="mt-2 block text-orange-400">Mehr Reichweite.</span></h1>
            <p class="mt-7 max-w-xl text-lg leading-8 text-slate-200">Teile deine Aufnahmen mit passenden YouTube-Kanälen. Ein zentraler Upload, automatische Verteilung und ein klarer Überblick über deine Clips.</p>
            <div class="mt-9 flex flex-col gap-4 sm:flex-row">
                <x-public.button :href="route('filament.standard.auth.register')"><x-heroicon-o-arrow-up-tray class="size-5" aria-hidden="true" />Clips hochladen<x-heroicon-o-arrow-right class="size-5" aria-hidden="true" /></x-public.button>
                <x-public.button href="#ablauf" variant="secondary">So funktioniert's</x-public.button>
            </div>
            <p class="mt-7 text-sm text-slate-300">Schon dabei? <a class="font-medium text-white underline underline-offset-4" href="{{ route('filament.standard.auth.login') }}">Jetzt anmelden</a></p>
        </div>
    </section>

    <section class="public-width py-10" aria-labelledby="benefits-title">
        <h2 id="benefits-title" class="sr-only">Dein Clip, einfach verteilt</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-public.feature-card title="Ein Upload" icon="heroicon-o-arrow-up-tray">Deine Aufnahmen an einem Ort, ohne sie für jeden Kanal erneut hochzuladen.</x-public.feature-card>
            <x-public.feature-card title="Passende Kanäle" icon="heroicon-o-video-camera">Die automatische Verteilung bringt Clips nach festen Regeln zu passenden Empfängern.</x-public.feature-card>
            <x-public.feature-card title="Direkt zum Download" icon="heroicon-o-arrow-down-tray">Kanäle sehen eine Vorschau und laden ausgewählte Clips einzeln oder als ZIP herunter.</x-public.feature-card>
            <x-public.feature-card title="Den Überblick behalten" icon="heroicon-o-clipboard-document-check">Angebote und Downloads werden protokolliert. So bleibt die Verteilung nachvollziehbar.</x-public.feature-card>
        </div>
    </section>

    <section id="ablauf" class="public-section public-width">
        <x-public.section-heading eyebrow="So funktioniert's" title="Von deiner Dashcam zum Kanal.">Drei Schritte, ein gemeinsamer Ablauf. Du kümmerst dich um deine Aufnahmen, die Plattform um die Verteilung.</x-public.section-heading>
        <ol class="grid gap-8 md:grid-cols-3">
            @foreach ([['Registrieren', 'Erstelle dein Konto und melde dich im Upload-Bereich an.'], ['Clips hochladen', 'Lade deine Aufnahmen hoch und ergänze die Informationen zu deinen Clips.'], ['Verteilen lassen', 'Geeignete Kanäle erhalten ein Angebot und entscheiden, welche Clips sie herunterladen.']] as [$title, $description])
                <li class="border-t border-border pt-6">
                    <span class="text-sm font-bold text-orange-700 dark:text-orange-400">0{{ $loop->iteration }}</span>
                    <h3 class="mt-4 mb-3 text-xl font-semibold">{{ $title }}</h3>
                    <p class="text-muted">{{ $description }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    <section id="kanaele" class="border-y border-border bg-panel">
        <div class="public-section public-width grid gap-12 lg:grid-cols-2 lg:gap-20">
            <div>
                <x-public.section-heading eyebrow="Für Einsender" title="Deine Aufnahmen. Ein zentraler Platz.">Ob besondere Begegnung oder alltägliche Verkehrssituation: Reiche deine Clips über deinen persönlichen Bereich ein und behalte ihre Verteilung im Blick.</x-public.section-heading>
                <x-public.button :href="route('filament.standard.auth.register')">Als Einsender registrieren</x-public.button>
            </div>
            <div class="rounded-2xl border border-border bg-bg p-7 sm:p-10">
                <x-heroicon-o-video-camera class="mb-6 size-8 text-orange-700 dark:text-orange-400" aria-hidden="true" />
                <h2 class="text-2xl font-bold">Für Kanäle</h2>
                <p class="mt-5 text-muted">Kanäle empfangen passende Angebote mit Vorschauen und zeitlich begrenzten Download-Links. Nicht benötigte Clips lassen sich zurückgeben.</p>
                <p class="mt-4 text-muted">Ein Angebot ist noch keine Veröffentlichung. Die Auswahl und Veröffentlichung liegen beim jeweiligen Kanal.</p>
                <a href="#angebote" class="mt-7 inline-flex min-h-11 items-center gap-3 font-semibold">Mehr über Angebote<x-heroicon-o-arrow-right class="size-5" aria-hidden="true" /></a>
            </div>
        </div>
    </section>

    <section id="angebote" class="public-section public-width">
        <x-public.section-heading eyebrow="Angebote & Downloads" title="Sichten. Auswählen. Herunterladen.">Alles, was ein Kanal für die Auswahl braucht, an einem Ort.</x-public.section-heading>
        <div class="grid gap-6 md:grid-cols-3">
            <x-public.feature-card title="Vorschau ansehen" icon="heroicon-o-play-circle">Prüfe die angebotenen Clips direkt auf der Angebotsseite, bevor du deine Auswahl triffst.</x-public.feature-card>
            <x-public.feature-card title="Einzeln oder als ZIP" icon="heroicon-o-folder-arrow-down">Lade einzelne Clips oder eine Auswahl gesammelt herunter. Beachte die Gültigkeit des Angebots.</x-public.feature-card>
            <x-public.feature-card title="Clips zurückgeben" icon="heroicon-o-arrow-uturn-left">Nicht benötigte Inhalte können über die Rückgabe-Funktion wieder für die Verteilung freigegeben werden.</x-public.feature-card>
        </div>
    </section>

    <section id="hilfe" class="border-t border-border bg-panel">
        <div class="public-section public-width grid gap-8 lg:grid-cols-2 lg:gap-20">
            <x-public.section-heading eyebrow="Hilfe" title="Gut zu wissen.">Die wichtigsten Antworten rund um Uploads und Angebote.</x-public.section-heading>
            <div class="divide-y divide-border border-y border-border">
                @foreach ([
                    'Muss ich meinen Clip für jeden Kanal hochladen?' => 'Nein. Ein zentraler Upload genügt. Die Plattform verteilt deine Clips nach ihren Verteilungsregeln an passende Kanäle.',
                    'Wird mein Clip automatisch veröffentlicht?' => 'Nein. Kanäle entscheiden selbst, welche angebotenen Clips sie verwenden und veröffentlichen.',
                    'Wie lange kann ein Kanal Clips herunterladen?' => 'Angebote sind zeitlich begrenzt. Die jeweilige Gültigkeit steht auf der Angebotsseite.',
                    'Was passiert mit nicht benötigten Clips?' => 'Kanäle können nicht benötigte Clips über die Angebotsseite zurückgeben, damit sie erneut verteilt werden können.',
                    'Wo kann ich meine Clips hochladen?' => 'Registriere dich und melde dich anschließend in deinem persönlichen Bereich an. Dort stehen dir die Upload-Funktionen zur Verfügung.',
                ] as $question => $answer)
                    <details class="py-5">
                        <summary class="pr-3 font-semibold">{{ $question }}</summary>
                        <p class="mt-4 text-muted">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-ink text-white">
        <div class="public-width flex flex-col items-start justify-between gap-8 py-16 lg:flex-row lg:items-center">
            <div><h2 class="text-3xl font-bold tracking-tight">Dein nächster Clip beginnt hier.</h2><p class="mt-4 text-slate-300">Einmal hochladen und passende Kanäle erreichen.</p></div>
            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                <x-public.button :href="route('filament.standard.auth.register')">Jetzt registrieren</x-public.button>
                <x-public.button :href="route('filament.standard.auth.login')" variant="secondary">Anmelden</x-public.button>
            </div>
        </div>
    </section>
@endsection
