<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scope tenant-independent panel routes to the user's default tenant so the full layout can render.
 */
class SetDefaultTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if ($user !== null && Filament::hasTenancy() && Filament::getTenant() === null) {
            $tenant = Filament::getUserDefaultTenant($user);

            if ($tenant !== null) {
                Filament::setTenant($tenant);
            }
        }

        return $next($request);
    }
}
