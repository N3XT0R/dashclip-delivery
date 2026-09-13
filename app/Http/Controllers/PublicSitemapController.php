<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PublicSitemapService;
use Illuminate\Http\Response;

class PublicSitemapController extends Controller
{
    /** Render the current canonical public URLs as UTF-8 sitemap XML. */
    public function __invoke(PublicSitemapService $sitemap): Response
    {
        return response()->view('sitemap', ['entries' => $sitemap->entries()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /** Advertise the root sitemap using the current deployment's absolute URL. */
    public function robots(): Response
    {
        return response("User-agent: *\nDisallow:\n\nSitemap: ".route('sitemap')."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
