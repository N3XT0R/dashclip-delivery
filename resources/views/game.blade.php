@extends('layouts.app')
@section('content_language', 'de')
@section('title', 'Sammle die Clips | DashClip Delivery')
@section('description', 'Das kleine DashClip-Spiel: Sammle grüne Clips und weiche dem roten Störer aus.')
@section('page_assets')
    @vite(['resources/js/game.js', 'resources/css/game.css'])
@endsection
@section('content')
    <section class="mx-auto max-w-3xl">
        <h1 class="mb-5 text-3xl font-bold">Mini-Game: Sammle die Clips</h1>
        <p id="game-instructions" class="mb-6 text-muted">Wähle das Spielfeld mit Tab oder einem Klick aus. Steuere den blauen Kreis mit den Pfeiltasten oder WASD. Sammle grüne Clips und weiche dem roten Störer aus. Du hast 90 Sekunden. Mit Tab verlässt du das Spielfeld wieder.</p>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap gap-5 text-sm">
                <span>Score: <span id="score">0</span></span>
                <span>Zeit: <span id="time">90</span>s</span>
                <span>Best: <span id="best">0</span></span>
            </div>
            <x-public.button id="restart">Neu starten</x-public.button>
        </div>
        <canvas id="gameCanvas" width="640" height="420" tabindex="0" aria-label="Spielfeld" aria-describedby="game-instructions">Dieses Spiel benötigt JavaScript und einen Browser mit Canvas-Unterstützung.</canvas>
    </section>
@endsection
