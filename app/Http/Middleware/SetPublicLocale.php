<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\LocaleDiscoveryService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPublicLocale
{
    public function __construct(private readonly LocaleDiscoveryService $locales)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = $this->locales->list();
        $saved = $request->cookie('public_locale');
        $fallback = config('app.locale');
        $preferred = array_values(array_unique([$fallback, ...$supported]));
        $locale = in_array($saved, $supported, true)
            ? $saved
            : $request->getPreferredLanguage($preferred);

        if ($request->routeIs('blog.*')) {
            $locale = $request->routeIs('blog.en.*') ? 'en' : 'de';
        }
        app()->setLocale($locale);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);
        $response->setVary(['Accept-Language', 'Cookie'], false);

        return $response;
    }
}
