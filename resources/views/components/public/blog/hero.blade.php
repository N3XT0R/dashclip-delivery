@props(['title', 'description' => null, 'article' => null])
<section class="relative isolate overflow-hidden bg-ink text-white">
    <img src="{{ asset('images/marketing/hero.jpg') }}" width="1280" height="720" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover">
    <div class="absolute inset-0 -z-10 bg-linear-to-r from-ink/95 via-ink/85 to-ink/40"></div>
    <div class="public-width py-12 sm:py-16">
        <p class="mb-4 text-sm font-semibold tracking-widest uppercase text-orange-400">DashClip · Blog</p>
        <h1 class="max-w-3xl text-3xl font-bold leading-tight sm:text-5xl">{{ $title }}</h1>
        @unless($article)<p class="mt-3 text-3xl font-bold text-orange-400">{{ __('blog.accent') }}</p>@endunless
        @if($description)<p class="mt-5 max-w-2xl text-lg text-slate-200">{{ $description }}</p>@endif
        @if($article)<div class="mt-6"><x-public.blog.meta :article="$article" /></div>@endif
    </div>
</section>
