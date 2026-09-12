@php
    $links = ['Home' => url('/'), "So funktioniert's" => url('/').'#ablauf', 'Kanäle' => url('/').'#kanaele', 'Angebote' => url('/').'#angebote', 'Hilfe' => url('/').'#hilfe'];
@endphp
<header class="border-b border-white/10 bg-ink text-white">
    <div class="public-width flex flex-wrap items-center justify-between gap-4 py-4">
        <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-3" aria-label="DashClip Delivery Startseite">
            <img src="{{ asset('images/marketing/logo.webp') }}" width="160" height="160" alt="" class="size-11 rounded-lg bg-white object-contain">
            <span class="text-lg leading-tight font-bold">DashClip<span class="block text-orange-400">Delivery</span></span>
        </a>
        <nav aria-label="Hauptnavigation" class="order-3 w-full lg:order-none lg:w-auto">
            <div class="hidden items-center gap-5 text-sm lg:flex">
                @foreach ($links as $label => $href)
                    <a href="{{ $href }}" class="py-3 hover:text-orange-400">{{ $label }}</a>
                @endforeach
            </div>
            <details id="mobile-navigation" class="lg:hidden">
                <summary class="min-h-11 py-3 font-semibold">Menü</summary>
                <div class="flex flex-col gap-1 pb-3">
                    @foreach ($links as $label => $href)
                        <a href="{{ $href }}" class="rounded-lg px-3 py-3 hover:bg-white/10">{{ $label }}</a>
                    @endforeach
                    <a href="{{ route('filament.standard.auth.login') }}" class="rounded-lg px-3 py-3 hover:bg-white/10">Anmelden</a>
                    <x-public.button :href="route('filament.standard.auth.register')">Registrieren</x-public.button>
                </div>
            </details>
        </nav>
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <a href="{{ route('filament.standard.auth.login') }}" class="hidden px-3 py-3 sm:block">Anmelden</a>
            <x-public.button :href="route('filament.standard.auth.register')" class="hidden sm:inline-flex">Registrieren</x-public.button>
            <button id="themeToggle" type="button" aria-label="Dunkles Farbschema" aria-pressed="false" class="flex size-11 items-center justify-center rounded-lg border border-white/30">
                <x-heroicon-o-moon class="size-5" aria-hidden="true" />
            </button>
        </div>
    </div>
</header>
