<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\LocaleDiscoveryService;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if ($user instanceof HasLocalePreference) {
            $locale = $user->preferredLocale();

            if (in_array($locale, app(LocaleDiscoveryService::class)->list(), true)) {
                App::setLocale($locale);
            }
        }

        return $next($request);
    }
}
