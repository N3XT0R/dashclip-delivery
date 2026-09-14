@props(['article'])
<div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs">
    <span>{{ $article->post->author->display_name }}</span>
    <time datetime="{{ $article->published_at?->toAtomString() }}">{{ $article->published_at?->translatedFormat('d. M Y') }}</time>
    <span>{{ __('blog.minutes', ['count' => $article->reading_minutes]) }}</span>
</div>
