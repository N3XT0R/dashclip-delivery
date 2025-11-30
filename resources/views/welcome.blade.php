<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DashClip Delivery</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/icons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/icons/favicon-16x16.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }

        .popup iframe {
            border: none;
            flex: 1;
            width: 100%;
            border-radius: 8px;
        }
    </style>
</head>
<body class="text-gray-800 flex items-center justify-center min-h-screen">
<div class="max-w-lg mx-auto p-8 bg-white rounded-xl shadow text-center">
    <img src="{{ asset('images/logo.png') }}" alt="DashClip Delivery Logo" class="mx-auto mb-4 h-16 w-auto">
    <h1 class="text-3xl font-extrabold mb-4 text-indigo-600">DashClip Delivery</h1>
    <p class="mb-6 text-gray-600">
        DashClip Delivery ist eine zentrale Plattform für Einsender, die ihre Dashcam-Clips
        an mehrere YouTube-Kanäle verteilen möchten – ohne mehrfachen Upload, ohne
        verschiedene Formulare und ohne den Überblick zu verlieren.
    </p>

    <p class="mb-6 text-gray-600">
        Du lädst deinen Clip einmal hoch. Die Plattform übernimmt anschließend automatisch
        die faire, nachvollziehbare Verteilung an die angeschlossenen Kanäle. Jeder Kanal
        erhält nur passende Angebote und kann Clips einzeln oder gesammelt herunterladen.
    </p>

    <p class="mb-6 text-gray-600">
        DashClip Delivery löst damit ein Problem, das viele Einsender haben: ein einziger
        Upload statt mehrere Portale. Kanäle sind reine Empfänger innerhalb dieses Systems.
        Downloads sind zeitlich begrenzt, werden protokolliert und nicht benötigte Clips
        können jederzeit zurückgegeben werden.
    </p>

    <ul class="list-disc pl-6 space-y-1 text-gray-600 mb-6 text-left">
        <li>Zentraler Upload für mehrere YouTube-Kanäle gleichzeitig</li>
        <li>Automatische, faire Verteilung von Clips anhand definierter Regeln</li>
        <li>Download-Links mit Vorschau und optionalem ZIP-Paket</li>
        <li>Rückgabe-Option für nicht benötigte Inhalte</li>
        <li>Transparente Protokollierung aller Aktionen</li>
    </ul>
    <div class="flex flex-col items-center gap-3 mt-6">
        <a href="{{ route('filament.standard.auth.register') }}" class="btn">
            Jetzt registrieren
        </a>

        <a href="{{ route('filament.standard.auth.login') }}" class="btn">
            Zum Login
        </a>
    </div>
    @include('partials.footer')
</div>
</body>
</html>