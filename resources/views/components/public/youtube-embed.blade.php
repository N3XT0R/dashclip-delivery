@props(['videoId', 'title'])
<figure {{ $attributes->class(['overflow-hidden rounded-xl border border-border bg-ink']) }}>
    <div class="relative aspect-video" data-youtube-embed="{{ 'https://www.youtube-nocookie.com/embed/'.rawurlencode($videoId).'?autoplay=1&rel=0' }}" data-youtube-title="{{ $title }}">
        <div class="absolute inset-0 flex flex-col items-center justify-center gap-5 p-6 text-center text-white">
            <button type="button" data-youtube-load class="flex size-16 items-center justify-center rounded-full bg-orange-600 text-white shadow-lg transition hover:bg-orange-500 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-orange-300" aria-label="{{ __('public.video_load', ['title' => $title]) }}">
                <x-heroicon-s-play class="size-8 translate-x-0.5" aria-hidden="true" />
            </button>
            <p class="max-w-md text-sm leading-6 text-slate-300">
                {{ __('public.video_consent') }}
                <a class="font-medium text-white underline underline-offset-4" href="{{ route('datenschutz') }}">{{ __('public.privacy_policy') }}</a>.
            </p>
        </div>
    </div>
    <figcaption class="sr-only">{{ $title }}</figcaption>
</figure>
