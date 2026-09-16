@php
    $links = [__('public.home') => url('/'), __('public.process') => url('/').'#ablauf', __('public.channels') => url('/').'#kanaele', __('public.offers') => url('/').'#angebote', __('public.help') => url('/').'#hilfe'];
@endphp
@php($links['Blog'] = route(app()->getLocale() === 'en' ? 'blog.en.index' : 'blog.index'))
<header class="border-b border-white/10 bg-ink text-white">
    <div class="public-width flex flex-wrap items-center justify-between gap-4 py-4">
        <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-3" aria-label="{{ __('public.home_link') }}">
            <x-public.brand />
        </a>
        <nav aria-label="{{ __('public.navigation') }}" class="order-3 w-full lg:order-none lg:w-auto">
            <div class="hidden items-center gap-5 text-sm lg:flex">
                @foreach ($links as $label => $href)
                    <a href="{{ $href }}" class="py-3 hover:text-orange-400">{{ $label }}</a>
                @endforeach
            </div>
            <details id="mobile-navigation" class="lg:hidden">
                <summary class="min-h-11 py-3 font-semibold">{{ __('public.menu') }}</summary>
                <div class="flex flex-col gap-1 pb-3">
                    @foreach ($links as $label => $href)
                        <a href="{{ $href }}" class="rounded-lg px-3 py-3 hover:bg-white/10">{{ $label }}</a>
                    @endforeach
                    <a href="{{ route('filament.standard.auth.login') }}" class="rounded-lg px-3 py-3 hover:bg-white/10">{{ __('public.login') }}</a>
                    <x-public.button :href="route('filament.standard.auth.register')">{{ __('public.register') }}</x-public.button>
                </div>
            </details>
        </nav>
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <a href="{{ route(app()->getLocale() === 'en' ? 'blog.en.search' : 'blog.search') }}" class="flex size-11 items-center justify-center" aria-label="{{ __('blog.search') }}"><x-heroicon-o-magnifying-glass class="size-5" aria-hidden="true" /></a>
            <x-public.language-selector />
            <a href="{{ route('filament.standard.auth.login') }}" class="hidden px-3 py-3 sm:block">{{ __('public.login') }}</a>
            <x-public.button :href="route('filament.standard.auth.register')" class="hidden sm:inline-flex">{{ __('public.register') }}</x-public.button>
            <button id="themeToggle" type="button" aria-label="{{ __('public.dark_theme') }}" aria-pressed="false" class="flex size-11 items-center justify-center rounded-lg border border-white/30">
                <x-heroicon-o-moon class="size-5" aria-hidden="true" />
            </button>
        </div>
    </div>
</header>
