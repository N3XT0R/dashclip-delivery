@props(['title', 'description', 'robots' => 'index, follow', 'indexable' => false, 'canonical' => null, 'image' => null, 'type' => 'website'])
@php
    $indexable = $indexable && $robots === 'index, follow';
    $page = request()->integer('page');
    $canonical = $canonical ?: url(request()->path()).($page > 1 ? '?page='.$page : '');
@endphp
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $indexable ? $robots : 'noindex, nofollow' }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="DashClip Delivery">
<meta property="og:locale" content="{{ ['de' => 'de_DE', 'en' => 'en_US'][app()->getLocale()] ?? app()->getLocale() }}">
<meta property="og:image" content="{{ $image ?: asset('images/marketing/hero.jpg') }}">
<meta name="twitter:card" content="summary_large_image">
@if ($indexable)
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:url" content="{{ $canonical }}">
@endif
