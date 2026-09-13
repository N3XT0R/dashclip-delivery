@props(['categories', 'topics'])
@inject('blog', 'App\Services\Blog\BlogPresentationService')
<aside class="space-y-6">
    <section class="rounded-xl border border-border bg-panel p-6">
        <h2 class="mb-5 text-xl font-bold">{{ __('blog.topics') }}</h2>
        <ul class="space-y-4">
            @foreach($categories as $category)
            <li><a class="flex justify-between gap-4 text-sm hover:underline" href="{{ $blog->url('category', app()->getLocale(), ['slug' => $category->slug]) }}"><span>{{ $category->name }}</span><span class="text-muted">{{ $category->article_count }}</span></a></li>
            @endforeach
        </ul>
        @if($topics->isNotEmpty())
        <div class="mt-6 flex flex-wrap gap-2">
            @foreach($topics as $topic)<a class="rounded-full border border-border px-3 py-2 text-xs hover:underline" href="{{ $blog->url('tag', app()->getLocale(), ['slug' => $topic->slug]) }}">{{ $topic->name }}</a>@endforeach
        </div>
        @endif
    </section>
    <section class="rounded-xl bg-ink p-6 text-white">
        <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-orange-400">{{ __('public.submitters') }}</p>
        <h2 class="mb-5 text-2xl font-bold">{{ __('public.headline') }}</h2>
        <p class="mb-6 text-sm text-slate-200">{{ __('public.closing_body') }}</p>
        <x-public.button :href="route('filament.standard.auth.register')">{{ __('public.upload') }}</x-public.button>
    </section>
    <a href="{{ $blog->url('feed', app()->getLocale()) }}" class="inline-block py-3 text-sm underline">{{ __('blog.rss') }}</a>
</aside>
