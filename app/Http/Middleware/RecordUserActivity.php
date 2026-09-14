<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordUserActivity
{
    /**
     * Track authenticated panel activity, including persistent Livewire requests.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if ($user instanceof User) {
            $user->recordActivity();
        }

        return $next($request);
    }
}
