<?php

declare(strict_types=1);

namespace App\Services\OAuth;

use App\Enum\Guard\GuardEnum;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use N3XT0R\FilamentPassportUi\Contracts\SelfServiceScopeResolver;

/**
 * Binds the OAuth scopes a user may put on their own client to what that user
 * is actually allowed to do in the Standard panel.
 *
 * The binding is not maintained by hand: every `/api/v1` route already declares
 * both the scope it needs (`scope:<resource>:<action>`) and the Standard-guard
 * permission it needs (`standard.permission:<Permission>`). This reads that
 * pairing back off the route table, so the allow-list can never drift from what
 * the endpoints actually enforce. A scope whose routes require no permission
 * (teams, which are governed by ownership alone) is available to everyone.
 */
final class RoleBoundScopeResolver implements SelfServiceScopeResolver
{
    private const string SCOPE_PREFIX = 'scope:';

    private const string PERMISSION_PREFIX = 'standard.permission:';

    public function allowedScopesFor(?Authenticatable $actor): ?Collection
    {
        if (!$actor instanceof User) {
            return collect();
        }

        return $this->scopePermissionMap()
            ->filter(fn (array $permissions): bool => $this->holdsAny($actor, $permissions))
            ->keys()
            ->sort()
            ->values();
    }

    /**
     * Every scope declared by the API routes, mapped to the Standard-guard
     * permissions that unlock it. An empty permission list means the scope is
     * not permission-gated.
     *
     * @return Collection<string, list<string>>
     */
    public function scopePermissionMap(): Collection
    {
        $map = [];

        foreach (RouteFacade::getRoutes() as $route) {
            $middleware = $this->middlewareOf($route);
            $scopes = $this->valuesWithPrefix($middleware, self::SCOPE_PREFIX);

            if ($scopes === []) {
                continue;
            }

            $permissions = $this->valuesWithPrefix($middleware, self::PERMISSION_PREFIX);

            foreach ($scopes as $scope) {
                $map[$scope] = array_values(array_unique([...($map[$scope] ?? []), ...$permissions]));
            }
        }

        return collect($map);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function holdsAny(User $actor, array $permissions): bool
    {
        if ($permissions === []) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($actor->checkPermissionTo($permission, GuardEnum::STANDARD->value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function middlewareOf(Route $route): array
    {
        return array_values(array_filter(
            $route->gatherMiddleware(),
            static fn (mixed $entry): bool => is_string($entry),
        ));
    }

    /**
     * @param  list<string>  $middleware
     * @return list<string>
     */
    private function valuesWithPrefix(array $middleware, string $prefix): array
    {
        return array_values(array_map(
            static fn (string $entry): string => Str::after($entry, $prefix),
            array_filter($middleware, static fn (string $entry): bool => str_starts_with($entry, $prefix)),
        ));
    }
}
