<?php

declare(strict_types=1);

namespace App\Http\Middleware\Api;

use App\Enum\Guard\GuardEnum;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStandardPermission
{
    /**
     * Ensure the authenticated api user holds the given standard-guard
     * permission; aborts with 403 otherwise.
     *
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        /** @var User|null $user */
        $user = $request->user('api');

        if ($user === null || !$user->checkPermissionTo($permission, GuardEnum::STANDARD->value)) {
            throw new AuthorizationException();
        }

        return $next($request);
    }
}
