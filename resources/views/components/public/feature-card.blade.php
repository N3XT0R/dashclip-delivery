@props(['title', 'icon'])
<article {{ $attributes->class(['rounded-xl border border-border bg-panel p-6']) }}>
    <div class="mb-5 flex size-12 items-center justify-center rounded-xl bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300">
        <x-dynamic-component :component="$icon" class="size-6" aria-hidden="true" />
    </div>
    <h3 class="mb-2 text-lg font-semibold">{{ $title }}</h3>
    <p class="text-sm leading-7 text-muted">{{ $slot }}</p>
</article>
