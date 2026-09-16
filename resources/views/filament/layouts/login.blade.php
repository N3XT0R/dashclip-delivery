<x-filament-panels::layout.base :livewire="$livewire">
    <div class="dc-login-layout">
        <a href="#fi-main-content" class="fi-skip-link fi-sr-only">{{ __('filament-panels::layout.skip_to_content.label') }}</a>
        <aside class="dc-login-story" aria-labelledby="login-story-title">
            <img class="dc-login-background" src="{{ asset('images/marketing/hero-1920.webp') }}" alt="" fetchpriority="high">
            <a class="dc-login-brand" href="{{ route('home') }}" aria-label="{{ __('public.home_link') }}"><x-public.brand /></a>
            <div class="dc-login-story-content">
                <p class="dc-login-eyebrow">{{ __('login.eyebrow') }}</p>
                <h2 id="login-story-title">{{ __('login.title') }}<span>{{ __('login.title_accent') }}</span></h2>
                <p class="dc-login-intro">{{ __('login.intro') }}</p>
                <ul class="dc-login-benefits">
                    @foreach (['uploads' => 'heroicon-o-video-camera', 'channels' => 'heroicon-o-computer-desktop', 'reach' => 'heroicon-o-chart-bar', 'security' => 'heroicon-o-shield-check'] as $benefit => $icon)
                        <li><span class="dc-login-benefit-icon"><x-filament::icon :icon="$icon" /></span><div><h3>{{ __('login.'.$benefit) }}</h3><p>{{ __('login.'.$benefit.'_hint') }}</p></div></li>
                    @endforeach
                </ul>
                <p class="dc-login-motto">{{ __('login.motto') }}</p>
            </div>
        </aside>
        <div class="dc-login-main">
            <div class="dc-login-language"><x-public.language-selector /></div>
            <main id="fi-main-content" tabindex="-1" class="dc-login-card">{{ $slot }}</main>
            <nav class="dc-login-footer" aria-label="{{ __('dashboard.help') }}">
                <a href="{{ route('impressum') }}">{{ __('dashboard.imprint') }}</a>
                <a href="{{ route('datenschutz') }}">{{ __('dashboard.privacy') }}</a>
                <a href="{{ route('tos') }}">{{ __('dashboard.terms') }}</a>
                <a href="{{ route('home') }}#hilfe">{{ __('dashboard.help') }}</a>
            </nav>
        </div>
    </div>
</x-filament-panels::layout.base>
