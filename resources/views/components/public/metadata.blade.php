@props(['title', 'description', 'robots' => 'index, follow'])
@php
    $indexable = $robots === 'index, follow' && request()->is('/', 'impressum', 'datenschutz', 'tos', 'api-docs', 'changelog', 'license', 'game');
@endphp
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $indexable ? $robots : 'noindex, nofollow' }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:type" content="website">
<meta property="og:image" content="{{ asset('images/marketing/hero.jpg') }}">
<meta name="twitter:card" content="summary_large_image">
@if ($indexable)
    <link rel="canonical" href="{{ url(request()->path()) }}">
    <meta property="og:url" content="{{ url(request()->path()) }}">
@endif
