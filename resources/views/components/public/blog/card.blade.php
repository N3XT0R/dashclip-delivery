@props(['article'])
@inject('blog', 'App\Services\Blog\BlogPresentationService')
<article class="flex h-full flex-col overflow-hidden rounded-xl border border-border bg-panel shadow-sm">
    <a href="{{ $blog->url('show', $article->locale, ['slug' => $article->slug]) }}" tabindex="-1" aria-hidden="true">
        <img src="{{ $blog->image($article) }}" alt="" width="640" height="360" loading="lazy" class="aspect-video w-full object-cover">
    </a>
    <div class="flex flex-1 flex-col items-start gap-4 p-5">
        <x-public.blog.category-badge :category="$article->post->category" />
        <h2 class="text-xl font-bold leading-snug"><a href="{{ $blog->url('show', $article->locale, ['slug' => $article->slug]) }}" class="hover:underline">{{ $article->title }}</a></h2>
        <p class="text-sm text-muted">{{ $article->excerpt }}</p>
        <div class="mt-auto border-t border-border pt-4 text-muted"><x-public.blog.meta :article="$article" /></div>
    </div>
</article>
