@props(['category'])
@if ($translation = $category?->translation(app()->getLocale()))
<a href="{{ route(app()->getLocale() === 'en' ? 'blog.en.category' : 'blog.category', $translation->slug) }}" class="inline-flex rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-900">{{ $translation->name }}</a>
@endif
