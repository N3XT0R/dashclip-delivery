<footer class="dc-footer">
    <div><strong>DashClip Delivery</strong><p>{{ __('dashboard.tagline') }}</p></div>
    <nav aria-label="{{ __('dashboard.help') }}">
        <a href="{{ route('home') }}#ablauf">{{ __('dashboard.help') }}</a>
        <a href="{{ route('datenschutz') }}">{{ __('dashboard.privacy') }}</a>
        <a href="{{ route('tos') }}">{{ __('dashboard.terms') }}</a>
        <a href="{{ route('impressum') }}">{{ __('dashboard.imprint') }}</a>
        <a href="{{ route('changelog') }}">Version {{ App\Facades\Version::getCurrentVersion() }}</a>
        @unless(app()->environment('production'))<span>{{ __('dashboard.test_instance') }}</span>@endunless
    </nav>
</footer>
