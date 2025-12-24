@extends('layouts.app')

@section('title', 'Willkommen')
@section('subtitle', null)

@section('content')
    <div class="panel">
        <img src="{{ asset('images/logo.png') }}"
             alt="DashClip Delivery Logo"
             class="mx-auto mb-4 h-16 w-auto">

        <h1 class="text-3xl font-extrabold mb-4 text-indigo-600 text-center">
            DashClip Delivery
        </h1>
        <p class="text-sm text-gray-500 text-center mb-6">
            Seit Mitte 2025 produktiv bei mehreren Dashcam-Kanälen im Einsatz.
        </p>

        <p class="mb-6">
            DashClip Delivery ist eine zentrale Plattform für Einsender, die ihre Dashcam-Clips
            an mehrere YouTube-Kanäle verteilen möchten – ohne mehrfachen Upload, ohne
            verschiedene Formulare und ohne den Überblick zu verlieren.
        </p>

        <p class="mb-6">
            Du lädst deinen Clip einmal hoch. Die Plattform übernimmt anschließend automatisch
            die faire, nachvollziehbare Verteilung an die angeschlossenen Kanäle. Jeder Kanal
            erhält nur passende Angebote und kann Clips einzeln oder gesammelt herunterladen.
        </p>

        <p class="mb-6">
            DashClip Delivery löst damit ein Problem, das viele Einsender haben: ein einziger
            Upload statt mehrere Portale. Kanäle sind reine Empfänger innerhalb dieses Systems.
            Downloads sind zeitlich begrenzt, werden protokolliert und nicht benötigte Clips
            können jederzeit zurückgegeben werden.
        </p>

        <ul class="list-disc pl-6 space-y-1 mb-6 text-left">
            <li>Zentraler Upload für mehrere YouTube-Kanäle gleichzeitig</li>
            <li>Automatische, faire Verteilung von Clips anhand definierter Regeln</li>
            <li>Download-Links mit Vorschau und optionalem ZIP-Paket</li>
            <li>Rückgabe-Option für nicht benötigte Inhalte</li>
            <li>Transparente Protokollierung aller Aktionen</li>
        </ul>

        <div class="flex items-center justify-center gap-6 mt-6">
            <a href="{{ route('filament.standard.auth.register') }}" class="btn">
                Jetzt registrieren
            </a>

            <a href="{{ route('filament.standard.auth.login') }}" class="btn">
                Zum Login
            </a>
        </div>
    </div>
@endsection
