@props(['articles'])
@if($articles->hasPages())
<nav aria-label="Pagination" class="mt-10 flex flex-wrap justify-center gap-3">
    @if($articles->previousPageUrl())<x-public.button :href="$articles->previousPageUrl()" variant="secondary">{{ __('blog.previous') }}</x-public.button>@endif
    <span class="px-4 py-3">{{ $articles->currentPage() }} / {{ $articles->lastPage() }}</span>
    @if($articles->nextPageUrl())<x-public.button :href="$articles->nextPageUrl()" variant="secondary">{{ __('blog.next') }}</x-public.button>@endif
</nav>
@endif
