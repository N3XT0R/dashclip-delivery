<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Documentation\ApiDocumentationDto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class ApiDocumentationService
{
    /**
     * Every documentation set declared in the l5-swagger config, with its
     * Swagger UI and raw-spec URLs. New entries in
     * config('l5-swagger.documentations') appear here automatically.
     *
     * @return Collection<int, ApiDocumentationDto>
     */
    public function all(): Collection
    {
        return collect(config('l5-swagger.documentations', []))
            ->map(fn (array $config, string $key): ApiDocumentationDto => new ApiDocumentationDto(
                key: $key,
                title: $config['api']['title'] ?? $key,
                uiUrl: $this->routeOrPath("l5-swagger.{$key}.api", $config['routes']['api'] ?? "{$key}/documentation"),
                specUrl: $this->routeOrPath("l5-swagger.{$key}.docs", $config['routes']['docs'] ?? 'docs'),
            ))
            ->values();
    }

    /**
     * Resolve a named l5-swagger route when it is registered, otherwise fall
     * back to the raw configured path (used for freshly added documentation
     * sets whose routes are only registered on the next boot).
     */
    private function routeOrPath(string $routeName, string $path): string
    {
        return Route::has($routeName) ? route($routeName) : url($path);
    }
}
