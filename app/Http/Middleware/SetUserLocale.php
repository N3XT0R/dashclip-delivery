<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            App::setLocale($user->preferredLocale());
        }

        return $next($request);
    }
}
