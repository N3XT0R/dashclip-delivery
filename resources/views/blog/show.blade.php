@extends('layouts.app')
@inject('blog', 'App\Services\Blog\BlogPresentationService')
@section('title', $article->meta_title ?: $article->title)
@section('description', $article->meta_description ?: $article->excerpt)
@section('full_width', '1')
@section('indexable', !$preview && $article->is_indexable ? '1' : '0')
@section('robots', !$preview && $article->is_indexable ? 'index, follow' : 'noindex, nofollow')
@section('canonical', $article->canonical_url ?: $blog->url('show', $article->locale, ['slug' => $article->slug]))
@section('social_image', $blog->image($article))
@section('og_type', 'article')
@section('content')
@if($preview)<p class="bg-orange-50 p-4 text-center font-semibold text-orange-900">{{ __('blog.preview') }}</p>@endif
<x-public.blog.hero :title="$article->title" :description="$article->excerpt" :article="$article" />
<div class="public-width py-10">
    <nav aria-label="Breadcrumb" class="mb-8 flex flex-wrap items-center gap-3 text-sm"><a class="underline" href="{{ $blog->url('index', $article->locale) }}">Blog</a><span aria-hidden="true">/</span><x-public.blog.category-badge :category="$article->post->category" /></nav>
    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_280px]">
        <article class="min-w-0">
            <img src="{{ $blog->image($article) }}" alt="" width="1280" height="720" class="mb-8 aspect-video w-full rounded-xl object-cover">
            <x-public.blog.article-content :html="$articleContent" />
            <nav aria-label="{{ __('blog.tags') }}" class="mt-8 flex flex-wrap gap-2">
                @foreach($article->post->tags as $tag)
                @if($translation = $tag->translation($article->locale))
                <a class="rounded-full border border-border px-3 py-2 text-sm hover:underline" href="{{ $blog->url('tag', $article->locale, ['slug' => $translation->slug]) }}">{{ $translation->name }}</a>
                @endif
                @endforeach
            </nav>
            @unless($preview)<x-public.blog.share :url="$blog->url('show', $article->locale, ['slug' => $article->slug])" :title="$article->title" />@endunless
        </article>
        <x-public.blog.sidebar :categories="$categories" :topics="$topics" />
    </div>
    <x-public.blog.related-articles :articles="$related" />
</div>
@endsection
@push('head')
@unless($preview)
@foreach($article->post->translations as $sibling)
@if($sibling->status->value === 'published' && $sibling->published_at?->isPast() && $sibling->is_indexable)
<link rel="alternate" hreflang="{{ $sibling->locale }}" href="{{ $blog->url('show', $sibling->locale, ['slug' => $sibling->slug]) }}">
@endif
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ $blog->languages($article)['de'] }}">
<x-public.structured-data :data="app(\App\Services\StructuredDataService::class)->article($article, $article->canonical_url ?: $blog->url('show', $article->locale, ['slug' => $article->slug]))" />
@endunless
@endpush
