<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetGuestLocale
{
    public function __construct(private readonly SetPublicLocale $publicLocale)
    {
    }

    /** Use the public language preference for guests while preserving signed-in account preferences. */
    public function handle(Request $request, Closure $next): Response
    {
        return Filament::auth()->guest()
            ? $this->publicLocale->handle($request, $next)
            : $next($request);
    }
}
