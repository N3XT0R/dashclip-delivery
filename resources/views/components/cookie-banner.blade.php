@if (!request()->hasCookie('cookie_consent'))
    <aside id="cookie-banner" aria-label="{{ __('public.cookies_label') }}" class="fixed inset-x-0 bottom-0 z-50 max-h-[50dvh] overflow-y-auto border-t border-border bg-panel pb-[env(safe-area-inset-bottom)] shadow-lg">
        <div class="public-width flex flex-col items-start justify-between gap-4 py-5 text-sm sm:flex-row sm:items-center">
            <p class="max-w-4xl text-muted">
                {{ __('public.cookies_body') }}
                <a href="{{ route('datenschutz') }}" class="underline underline-offset-4">{{ __('public.privacy_policy') }}</a>.
            </p>
            <x-public.button id="cookie-accept" variant="secondary">OK</x-public.button>
        </div>
    </aside>
@endif
