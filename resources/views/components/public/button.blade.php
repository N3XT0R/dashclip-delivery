@props(['href' => null, 'variant' => 'primary'])
@php($classes = 'public-button '.($variant === 'primary' ? 'public-button-primary' : 'public-button-secondary'))
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class([$classes]) }}>{{ $slot }}</button>
@endif
