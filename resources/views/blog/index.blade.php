@extends('layouts.app')
@section('title', $heading.' · DashClip Delivery')
@section('description', __('blog.intro'))
@section('full_width', '1')
@section('indexable', $term === '' && !request()->routeIs('*.search') ? '1' : '0')
@section('robots', $term === '' && !request()->routeIs('*.search') ? 'index, follow' : 'noindex, nofollow')
@section('content')
<x-public.blog.hero :title="$heading" :description="__('blog.intro')" />
<div class="public-width py-10">
    <form action="{{ route(app()->getLocale() === 'en' ? 'blog.en.search' : 'blog.search') }}" method="GET" class="mb-6 flex max-w-xl gap-3">
        <label for="blog-search" class="sr-only">{{ __('blog.search') }}</label>
        <input id="blog-search" name="q" value="{{ $term }}" maxlength="200" placeholder="{{ __('blog.search_placeholder') }}" class="min-w-0 flex-1 rounded-lg border border-border bg-panel px-4 py-3">
        <x-public.button type="submit">{{ __('blog.search') }}</x-public.button>
    </form>
    <nav aria-label="{{ __('blog.categories') }}" class="mb-8 flex flex-wrap gap-2">
        <a href="{{ route(app()->getLocale() === 'en' ? 'blog.en.index' : 'blog.index') }}" class="rounded-full bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-900">{{ __('blog.all') }}</a>
        @foreach($categories as $category)<a href="{{ route(app()->getLocale() === 'en' ? 'blog.en.category' : 'blog.category', $category->slug) }}" class="rounded-full border border-border bg-panel px-4 py-2 text-sm hover:underline">{{ $category->name }}</a>@endforeach
    </nav>
    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_280px]">
        <div>
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @forelse($articles as $article)<x-public.blog.card :article="$article" />
                @empty<p class="rounded-xl border border-border bg-panel p-8 sm:col-span-2 xl:col-span-3">{{ $term !== '' ? __('blog.empty_search') : __('blog.empty') }}</p>@endforelse
            </div>
            <x-public.blog.pagination :articles="$articles" />
        </div>
        <x-public.blog.sidebar :categories="$categories" :topics="$topics" />
    </div>
</div>
@endsection
@push('head')
@foreach($alternates as $locale => $href)
<link rel="alternate" hreflang="{{ $locale }}" href="{{ $href }}">
@endforeach
@if($alternates !== [])
<link rel="alternate" hreflang="x-default" href="{{ $alternates['de'] ?? reset($alternates) }}">
@endif
@endpush
