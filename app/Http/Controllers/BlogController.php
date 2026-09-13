<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Blog\ShowPostUseCase;
use App\Exceptions\Blog\PostNotPublishedException;
use App\Models\PostTranslation;
use App\Repository\PostRepository;
use App\Services\Blog\BlogPresentationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class BlogController extends Controller
{
    public function __construct(private readonly PostRepository $posts, private readonly BlogPresentationService $presentation)
    {
    }

    /** List published articles, optionally narrowing by search, category or tag. */
    public function index(Request $request, ?string $slug = null): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:200']]);
        $locale = app()->getLocale();
        $term = trim((string)$request->query('q', ''));
        $query = $term !== '' ? $this->posts->search($locale, $term) : $this->posts->publishedForLocale($locale);
        $heading = __('blog.title');
        if ($request->routeIs('*.category')) {
            $category = $this->posts->category($locale, $slug);
            $heading = $category->name;
            $query->whereHas('post', fn ($query) => $query->where('category_id', $category->category_id));
        }
        if ($request->routeIs('*.tag')) {
            $tag = $this->posts->tag($locale, $slug);
            $heading = $tag->name;
            $query->whereHas('post.tags', fn ($query) => $query->whereKey($tag->tag_id));
        }
        return view('blog.index', [
            'articles' => $query->paginate(9)->withQueryString(), 'heading' => $heading, 'term' => $term,
            ...$this->sidebar($locale),
        ]);
    }

    /** Display a published translation; drafts and missing translations return 404. */
    public function show(string $slug, ShowPostUseCase $show): View
    {
        try {
            $article = $show->execute(app()->getLocale(), $slug);
        } catch (PostNotPublishedException) {
            abort(404);
        }
        return $this->articleView($article);
    }

    /** Preview saved editorial content only for users authorized to edit the post. */
    public function preview(PostTranslation $translation): View
    {
        Gate::authorize('update', $translation->post);
        app()->setLocale($translation->locale);
        return $this->articleView($translation, true);
    }

    /** Publish an RSS feed containing only public translations. */
    public function feed(): Response
    {
        return response()->view('blog.feed', [
            'articles' => $this->posts->publishedForLocale(app()->getLocale())->limit(30)->get(),
        ])->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    /** Publish blog URLs without drafts or explicitly non-indexable articles. */
    public function sitemap(): Response
    {
        $entries = [];
        foreach (['de', 'en'] as $locale) {
            $entries[] = $this->presentation->url('index', $locale);
            foreach ($this->posts->publishedForLocale($locale)->where('is_indexable', true)->get() as $article) {
                $entries[] = $this->presentation->url('show', $locale, ['slug' => $article->slug]);
            }
            foreach ($this->posts->categories($locale)->where('article_count', '>', 0) as $category) {
                $entries[] = $this->presentation->url('category', $locale, ['slug' => $category->slug]);
            }
            foreach ($this->posts->topics($locale) as $tag) {
                $entries[] = $this->presentation->url('tag', $locale, ['slug' => $tag->slug]);
            }
        }
        return response()->view('blog.sitemap', compact('entries'))->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /** Assemble reusable public article data without depending on the administration panel. */
    private function articleView(PostTranslation $article, bool $preview = false): View
    {
        $article->loadMissing(['post.author', 'post.translations', 'post.category.translations', 'post.tags.translations']);
        request()->attributes->set('blog_language_urls', $this->presentation->languages($article));
        return view('blog.show', [
            'article' => $article, 'preview' => $preview,
            'articleContent' => $this->presentation->content($article->content),
            'related' => $this->posts->publishedForLocale($article->locale)->where('post_id', '!=', $article->post_id)
                ->whereHas('post', fn ($query) => $query->where('category_id', $article->post->category_id))->limit(3)->get(),
            ...$this->sidebar($article->locale),
        ]);
    }

    /** @return array<string, mixed> Public sidebar collections. */
    private function sidebar(string $locale): array
    {
        return ['categories' => $this->posts->categories($locale), 'topics' => $this->posts->topics($locale)];
    }
}
