@if (!request()->hasCookie('cookie_consent'))
    <aside id="cookie-banner" aria-label="Hinweis zu Cookies" class="border-t border-border bg-panel">
        <div class="public-width flex flex-col items-start justify-between gap-4 py-5 text-sm sm:flex-row sm:items-center">
            <p class="max-w-4xl text-muted">
                Diese Website verwendet ausschließlich technisch notwendige Cookies.
                Weitere Informationen findest du in unserer
                <a href="{{ route('datenschutz') }}" class="underline underline-offset-4">Datenschutzerklärung</a>.
            </p>
            <x-public.button id="cookie-accept" variant="secondary">OK</x-public.button>
        </div>
    </aside>
@endif
