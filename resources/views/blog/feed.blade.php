{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
@inject('blog', 'App\Services\Blog\BlogPresentationService')
<rss version="2.0"><channel>
<title>DashClip Blog</title><link>{{ $blog->url('index', app()->getLocale()) }}</link><description>{{ __('blog.intro') }}</description><language>{{ app()->getLocale() }}</language>
@foreach($articles as $article)
<item><title>{{ $article->title }}</title><link>{{ $blog->url('show', $article->locale, ['slug' => $article->slug]) }}</link><guid>{{ $blog->url('show', $article->locale, ['slug' => $article->slug]) }}</guid><description>{{ $article->excerpt }}</description><pubDate>{{ $article->published_at->toRssString() }}</pubDate></item>
@endforeach
</channel></rss>
