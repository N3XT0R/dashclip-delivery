@props(['eyebrow', 'title'])
<div {{ $attributes->class(['mb-10 max-w-2xl']) }}>
    <p class="mb-3 text-sm font-semibold tracking-widest text-orange-700 uppercase dark:text-orange-400">{{ $eyebrow }}</p>
    <h2 class="text-3xl leading-tight font-bold tracking-tight sm:text-4xl">{{ $title }}</h2>
    @if ($slot->isNotEmpty())
        <div class="mt-5 text-lg text-muted">{{ $slot }}</div>
    @endif
</div>
