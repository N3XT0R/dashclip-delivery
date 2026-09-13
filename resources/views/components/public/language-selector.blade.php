<form method="POST" action="{{ route('public.locale') }}" aria-label="{{ __('public.language') }}" class="flex gap-1">
    @csrf
    <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
    @foreach (app(\App\Services\LocaleDiscoveryService::class)->list() as $locale)
        <button type="submit" name="locale" value="{{ $locale }}" lang="{{ $locale }}"
                aria-label="{{ app(\App\Services\LocaleDiscoveryService::class)->label($locale) }}"
                aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}"
                @class(['min-h-11 min-w-11 rounded-lg px-2 text-sm font-semibold', 'bg-white/15 ring-1 ring-white/40' => app()->getLocale() === $locale])>
            {{ strtoupper($locale) }}
        </button>
    @endforeach
</form>
