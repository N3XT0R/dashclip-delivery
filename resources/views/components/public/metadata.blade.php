@props(['title', 'description', 'robots' => 'index, follow', 'indexable' => false, 'canonical' => null, 'image' => null, 'type' => 'website'])
@php
    $indexable = $indexable && $robots === 'index, follow';
@endphp
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $indexable ? $robots : 'noindex, nofollow' }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:image" content="{{ $image ?: asset('images/marketing/hero.jpg') }}">
<meta name="twitter:card" content="summary_large_image">
@if ($indexable)
    <link rel="canonical" href="{{ $canonical ?: url(request()->path()) }}">
    <meta property="og:url" content="{{ $canonical ?: url(request()->path()) }}">
@endif
