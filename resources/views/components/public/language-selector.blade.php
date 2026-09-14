@if (request()->routeIs('blog.*'))
    <nav aria-label="{{ __('public.language') }}" class="flex gap-1">
        @foreach (request()->attributes->get('blog_language_urls', app(\App\Services\Blog\BlogPresentationService::class)->languages()) as $locale => $href)
            <x-public.language-option :locale="$locale" :href="$href" />
        @endforeach
    </nav>
@else
<form method="POST" action="{{ route('public.locale') }}" aria-label="{{ __('public.language') }}" class="flex gap-1">
    @csrf
    <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
    @foreach (app(\App\Services\LocaleDiscoveryService::class)->list() as $locale)
        <x-public.language-option :locale="$locale" />
    @endforeach
</form>
@endif
