<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\PostTranslation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class BlogPresentationService
{
    /**
     * Build a public address in the requested blog language.
     * @param array<string, string> $parameters Named route parameters such as the translated slug.
     */
    public function url(string $action, string $locale, array $parameters = []): string
    {
        return route('blog.'.($locale === 'en' ? 'en.' : '').$action, $parameters);
    }

    /** Resolve a public image through the existing public storage disk. */
    public function image(PostTranslation $article): string
    {
        return $article->post->image_path
            ? Storage::disk('public')->url($article->post->image_path)
            : asset('images/marketing/hero.jpg');
    }

    /** Render editor Markdown with raw HTML and unsafe links disabled. */
    public function content(string $markdown): string
    {
        $html = Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        return preg_replace_callback('/<blockquote>\s*<p>\[!(NOTE|WARNING)\]\s*/', static function (array $matches): string {
            $kind = $matches[1] === 'WARNING' ? 'warning' : 'note';
            return '<blockquote class="blog-callout blog-callout-'.$kind.'"><p><strong>'.e(__('blog.'.$kind)).'</strong><br>';
        }, $html) ?? $html;
    }

    /**
     * Return only currently published sibling addresses, with overview fallbacks.
     * @return array{de: string, en: string}
     */
    public function languages(?PostTranslation $article = null): array
    {
        $links = ['de' => $this->url('index', 'de'), 'en' => $this->url('index', 'en')];
        foreach ($article?->post->translations ?? [] as $translation) {
            if ($translation->status->value === 'published' && $translation->published_at?->isPast()) {
                $links[$translation->locale] = $this->url('show', $translation->locale, ['slug' => $translation->slug]);
            }
        }
        return $links;
    }

    /**
     * Map translated taxonomy slugs to their addresses in each blog language.
     * @param iterable<object{locale: string, slug: string}> $translations Category or tag translations.
     * @return array<string, string>
     */
    public function taxonomyLanguages(string $action, iterable $translations): array
    {
        $links = [];
        foreach ($translations as $translation) {
            $links[$translation->locale] = $this->url($action, $translation->locale, ['slug' => $translation->slug]);
        }
        ksort($links);
        return $links;
    }
}
