@php
    use App\Facades\Version;
    $channels = ['RLP Dashcam', 'Lets Dashcam', 'Augen auf!', 'Road Rave Germany', 'NEDK - NOCH EIN DASHCAM KANAL', 'Dashcam Stories'];
@endphp
<footer class="border-t border-border bg-panel">
    <div class="public-width py-14">
        <h2 class="mb-6 text-sm font-semibold tracking-widest uppercase">{{ __('public.channels') }}</h2>
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($channels as $channel)
                <li class="flex items-center gap-3 rounded-lg border border-border px-4 py-3 text-sm">
                    <x-heroicon-o-video-camera class="size-6 shrink-0 text-muted" aria-hidden="true" />
                    <span>{{ $channel }}</span>
                </li>
            @endforeach
        </ul>
        <div class="mt-12 flex flex-col justify-between gap-8 border-t border-border pt-8 lg:flex-row">
            <div>
                <p class="font-semibold">DashClip Delivery</p>
                <p class="mt-2 text-sm text-muted">{{ __('public.footer_tagline') }}</p>
                <p class="mt-3 text-xs text-muted">&copy; {{ date('Y') }} · Version <a class="underline" href="{{ route('changelog') }}">{{ Version::getCurrentVersion() }}</a></p>
            </div>
            <nav aria-label="{{ __('public.legal_navigation') }}" class="flex max-w-xl flex-wrap content-start gap-x-5 gap-y-3 text-sm text-muted">
                @foreach (['impressum' => __('public.imprint'), 'datenschutz' => __('public.privacy'), 'tos' => __('public.terms'), 'license' => __('public.license'), 'api-docs' => 'API-Docs', 'changelog' => 'Changelog'] as $name => $label)
                    <a class="underline-offset-4 hover:underline" href="{{ route($name) }}">{{ $label }}</a>
                @endforeach
                <a href="{{ url(config('app.footer.roadmap')) }}">Roadmap</a>
                <a href="{{ url(config('app.footer.issues')) }}">{{ __('public.issues') }}</a>
                <a href="https://github.com/N3XT0R/dashclip-delivery">GitHub</a>
            </nav>
        </div>
        @unless (app()->environment('production'))
            <p class="mt-8 rounded-lg border border-amber-600 bg-amber-50 p-3 text-sm font-semibold text-amber-950">{{ __('public.test_instance') }}</p>
        @endunless
    </div>
</footer>
