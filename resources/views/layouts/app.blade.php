<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DashClip Delivery')</title>
    <x-public.metadata
        :title="trim($__env->yieldContent('title', 'DashClip Delivery'))"
        :description="trim($__env->yieldContent('description', 'Dashcam-Clips einmal hochladen und nachvollziehbar an passende Kanäle verteilen.'))"
        :robots="trim($__env->yieldContent('robots', 'index, follow'))" />
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/icons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/icons/favicon-16x16.png') }}">
    <script>
        try { document.documentElement.classList.toggle('dark', localStorage.getItem('theme') === 'dark'); } catch {}
    </script>
    @yield('head')
    @vite(['resources/css/public.css', 'resources/js/public.js'])
    @yield('page_assets')
    @stack('styles')
</head>
<body class="flex min-h-screen flex-col">
<a href="#main-content" class="sr-only z-50 rounded-lg bg-panel p-4 text-text focus:not-sr-only focus:absolute focus:top-3 focus:left-3">Zum Inhalt springen</a>
<x-public.navigation />
<main id="main-content" tabindex="-1" class="flex-1 {{ $__env->hasSection('full_width') ? '' : 'public-width py-12 sm:py-16' }}">
    @if (session('status') || $errors->any() || $__env->hasSection('actions') || $__env->hasSection('subtitle'))
        <div class="{{ $__env->hasSection('full_width') ? 'public-width' : '' }}">
            @hasSection('subtitle')<p class="mb-4 text-muted">@yield('subtitle')</p>@endif
            @hasSection('actions')<div class="mb-6 flex flex-wrap gap-3">@yield('actions')</div>@endif
            @if (session('status'))
                <div class="flash flash--ok" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="flash flash--err" role="alert">
                    <strong>Bitte prüfe deine Eingaben:</strong>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif
    @yield('content')
</main>
<x-public.footer />
@include('components.cookie-banner')
@stack('scripts')
</body>
</html>
