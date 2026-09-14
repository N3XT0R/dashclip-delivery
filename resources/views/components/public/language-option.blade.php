@props(['locale', 'href' => null])

@php
    $active = app()->getLocale() === $locale;
    $attributes = $attributes->class([
        'inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg px-2 text-sm font-semibold',
        'bg-white/15 ring-1 ring-white/40' => $active,
    ])->merge([
        'lang' => $locale,
        'aria-label' => app(\App\Services\LocaleDiscoveryService::class)->label($locale),
    ]);
@endphp

@if ($href !== null)
    <a href="{{ $href }}" hreflang="{{ $locale }}" aria-current="{{ $active ? 'page' : 'false' }}" {{ $attributes }}>{{ strtoupper($locale) }}</a>
@else
    <button type="submit" name="locale" value="{{ $locale }}" aria-pressed="{{ $active ? 'true' : 'false' }}" {{ $attributes }}>{{ strtoupper($locale) }}</button>
@endif
