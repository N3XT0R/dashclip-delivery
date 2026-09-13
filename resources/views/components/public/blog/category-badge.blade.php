@props(['category'])
@if ($translation = $category?->translation(app()->getLocale()))
@php($icon = ['video' => 'heroicon-o-video-camera', 'shield' => 'heroicon-o-shield-check', 'camera' => 'heroicon-o-camera', 'news' => 'heroicon-o-newspaper', 'settings' => 'heroicon-o-cog-6-tooth'][$category->icon] ?? null)
<a href="{{ route(app()->getLocale() === 'en' ? 'blog.en.category' : 'blog.category', $translation->slug) }}" class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-900">@if($icon)<x-dynamic-component :component="$icon" class="size-4" aria-hidden="true" />@endif{{ $translation->name }}</a>
@endif
