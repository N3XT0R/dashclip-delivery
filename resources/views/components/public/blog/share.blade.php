@props(['url', 'title'])
@php
    $encoded = rawurlencode($url);
    $links = [
        'X' => 'https://twitter.com/intent/tweet?url='.$encoded.'&text='.rawurlencode($title),
        'Facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.$encoded,
        'LinkedIn' => 'https://www.linkedin.com/sharing/share-offsite/?url='.$encoded,
        'WhatsApp' => 'https://wa.me/?text='.rawurlencode($title.' '.$url),
        'E-Mail' => 'mailto:?subject='.rawurlencode($title).'&body='.$encoded,
    ];
@endphp
<div class="mt-8 border-t border-border pt-6">
    <h2 class="mb-4 text-lg font-bold">{{ __('blog.share') }}</h2>
    <div class="flex flex-wrap gap-2">
        <button type="button" data-copy-link="{{ $url }}" data-copied-label="{{ __('blog.copied') }}" class="rounded-lg border border-border px-4 py-3 text-sm">{{ __('blog.copy') }}</button>
        @foreach($links as $label => $href)<a href="{{ $href }}" rel="noopener noreferrer" class="rounded-lg border border-border px-4 py-3 text-sm hover:underline">{{ $label }}</a>@endforeach
    </div>
    <p class="sr-only" data-copy-status aria-live="polite"></p>
</div>
