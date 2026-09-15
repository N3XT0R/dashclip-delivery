@props(['article'])
@inject('blog', 'App\Services\Blog\BlogPresentationService')
@php($url = $blog->url('show', $article->locale, ['slug' => $article->slug]))
<a href="{{ $url }}" class="group relative block overflow-hidden rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">
    <img src="{{ $blog->image($article) }}" alt="" width="640" height="360" loading="lazy" class="aspect-video w-full object-cover transition duration-300 group-hover:scale-105">
    <span class="absolute inset-0 bg-linear-to-t from-black/85 via-black/40 to-transparent" aria-hidden="true"></span>
    <span class="absolute inset-x-0 bottom-0 line-clamp-2 p-3 text-sm leading-snug font-semibold text-white">{{ $article->title }}</span>
</a>
