# REST API Core Resources (Sub-Projekt 2) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development
> (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use
> checkbox (`- [ ]`) syntax for tracking.

**Goal:** REST-Endpoints für Videos, Channels, Offers (=Assignments) und Teams unter `/api/v1/`
mit Scope-/Permission-Autorisierung, Query-Parametern, `meta.pagination`-Format und vollständiger
l5-swagger-Dokumentation.

**Architecture:** Schlanke Controller (`app/Http/Controllers/Api/V1/`) + FormRequests + Eloquent
API Resources; Filter/Sort via `spatie/laravel-query-builder`; Sichtbarkeit über vorhandene
Model-Scopes; Token-Scopes via Passport-Middleware (Taxonomie aus
`n3xt0r/laravel-passport-authorization-core`); Aktions-Berechtigung explizit gegen das
Standard-Guard-Permission-Set (Policies greifen unter `api`-Guard NICHT — empirisch verifiziert).

**Tech Stack:** Laravel 13, PHP 8.4, Laravel Passport 13, spatie/laravel-query-builder,
darkaonline/l5-swagger, PHPUnit 12 (paratest).

**Spec:** `docs/superpowers/specs/2026-08-22-rest-api-core-resources-design.md`

## Global Constraints

- Tests laufen NUR im Docker-Container: `docker exec dashclip-delivery-sharing-1 php artisan test
  --parallel <pfad>` (Host-PHP hat kein mbstring). Composer ebenso:
  `docker exec dashclip-delivery-sharing-1 composer <cmd>`.
- Jede Commit-Message referenziert das Ticket `#250`.
- PSR-12 (`pint.json`, preset psr12); `declare(strict_types=1);` in jeder neuen PHP-Datei.
- Testmethoden im Projektstil: `public function testCamelCaseName(): void`, Klassen `final`,
  Basisklasse `Tests\DatabaseTestCase` (seedet `DatabaseSeeder` inkl. `ShieldSeeder` in setUp).
- CHANGELOG-Zeilen ≤120 Zeichen, hart umbrochen, Stil wie bestehende Einträge.
- `phpunit.xml` hat `failOnRisky/failOnWarning/failOnDeprecation` — keine Tests ohne Assertions,
  keine Deprecation-Warnungen einführen.
- ADRs in `docs/adr/` sind verbindlich: keine PHPUnit-Mocks/Stubs (ADR-0001; Laravel-Fakes wie
  `Storage::fake`/`Event::fake` sind ok und etabliert), Klassen-Suffixe nach Rolle (ADR-0002;
  UseCases unter `app/Application/<Domain>/<Name>UseCase.php`), SOLID/SRP — Controller nur
  HTTP-Entry, Orchestrierung in UseCases/Services (ADR-0003), domänenspezifische Exceptions statt
  SPL (ADR-0004), PHPDoc auf public Methods mit unklarem Kontrakt + importierte Kurznamen in
  Docblocks (ADR-0005), Conventional Commits (ADR-0007).
- Scope-Namensformat des Pakets ist `<resource>:<action>` (Separator `:`), z.B. `videos:read`.
- Sichtbarkeitsregel: fremde Datensätze liefern `404`, nie `403` (kein Existenz-Leak).

## Berechtigungs-Matrix (verbindlich für alle Tasks)

| Endpoint                      | Token-Scope     | Standard-Guard-Permission        |
|-------------------------------|-----------------|----------------------------------|
| GET /videos, GET /videos/{v}  | `videos:read`   | `ViewAny:Video`                  |
| POST /videos                  | `videos:write`  | `Create:Video`                   |
| PATCH /videos/{v}             | `videos:write`  | `Update:Video`                   |
| DELETE /videos/{v}            | `videos:delete` | `Delete:Video`                   |
| GET /channels, /channels/{c}  | `channels:read` | `ManageChannels:Team`            |
| PATCH /channels/{c}           | `channels:write`| `ManageChannels:Team`            |
| GET /offers, /offers/{o}      | `offers:read`   | `View:MyOffers`                  |
| POST /offers, /{o}/comment    | `offers:write`  | `View:MyOffers`                  |
| GET /teams, /teams/{t}        | `teams:read`    | — (nur Sichtbarkeit)             |
| POST /teams                   | `teams:write`   | — (jeder auth. User)             |
| DELETE /teams/{t}             | `teams:delete`  | — (nur `owner_id` === User-ID)   |

Rollen für Tests: `User::factory()->standard()` = `panel_user`@standard (hat die Video-Perms +
`ManageChannels:Team`); für Offers zusätzlich
`->withRole(RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD->value)` (hat `View:MyOffers`).

---

### Task 1: Pakete, API-Grundgerüst & JSON-Fehlerverhalten

**Files:**
- Modify: `composer.json`/`composer.lock` (via composer require)
- Create: `config/api.php`
- Modify: `bootstrap/app.php`
- Create: `app/Http/Controllers/Api/V1/ApiController.php`
- Modify: `.gitignore` (Zeile für `/storage/api-docs`)
- Test: `tests/Feature/Http/Api/V1/ApiErrorHandlingTest.php`

**Interfaces:**
- Consumes: `App\Enum\Guard\GuardEnum::STANDARD`, bestehende Route `GET /api/user` (auth:api).
- Produces (für alle Folge-Tasks):
  - `ApiController::authorizeApi(Request $request, string $permission): void` (wirft
    `AuthorizationException` → 403)
  - `ApiController::pageSize(Request $request): int` / `pageNumber(Request $request): int`
  - `ApiController::paginated(AnonymousResourceCollection $collection): JsonResponse`
    (Format `data`/`meta.pagination{current_page,per_page,total,total_pages}`/`links`)
  - `ApiController::noContent(): Response`
  - Middleware-Aliase `scope:` (`CheckTokenForAnyScope`) und `scopes:` (`CheckToken`)
  - Config-Keys `api.pagination.default_size` (25), `api.pagination.max_size` (100)

- [ ] **Step 1: Pakete installieren**

```bash
docker exec dashclip-delivery-sharing-1 composer require spatie/laravel-query-builder darkaonline/l5-swagger
docker exec dashclip-delivery-sharing-1 php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

Danach in `.gitignore` unter der Zeile `/storage/*.key` ergänzen: `/storage/api-docs`.

- [ ] **Step 2: Failing Tests schreiben**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class ApiErrorHandlingTest extends DatabaseTestCase
{
    public function testUnauthenticatedApiRequestReturnsJson401(): void
    {
        $response = $this->getJson('/api/user');
        $response->assertUnauthorized()->assertJsonStructure(['message']);
    }

    public function testMissingScopeReturnsJson403(): void
    {
        Route::middleware(['auth:api', 'scope:videos:read'])
            ->get('/api/v1/_scope-probe', static fn () => response()->json(['ok' => true]));
        $user = User::factory()->standard()->create();
        Passport::actingAs($user, ['channels:read']);

        $this->getJson('/api/v1/_scope-probe')->assertForbidden();
    }

    public function testPaginationConfigDefaults(): void
    {
        $this->assertSame(25, config('api.pagination.default_size'));
        $this->assertSame(100, config('api.pagination.max_size'));
    }
}
```

- [ ] **Step 3: Tests laufen lassen — erwartet FAIL**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/V1/ApiErrorHandlingTest.php`
Expected: FAIL (`Target class [scope] does not exist` bzw. Config null).

- [ ] **Step 4: Implementieren**

`config/api.php`:

```php
<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_size' => 25,
        'max_size' => 100,
    ],
];
```

`bootstrap/app.php` — `use`-Block ergänzen und `withMiddleware`/`withExceptions` erweitern:

```php
use Laravel\Passport\Http\Middleware\CheckToken;
use Laravel\Passport\Http\Middleware\CheckTokenForAnyScope;

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['cookie_consent']);
        $middleware->alias([
            'scope' => CheckTokenForAnyScope::class,
            'scopes' => CheckToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            static fn ($request) => $request->is('api/*') || $request->expectsJson()
        );
    })
```

`app/Http/Controllers/Api/V1/ApiController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enum\Guard\GuardEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

abstract class ApiController extends Controller
{
    protected function apiUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user('api');

        return $user;
    }

    /**
     * @throws AuthorizationException
     */
    protected function authorizeApi(Request $request, string $permission): void
    {
        if (!$this->apiUser($request)->checkPermissionTo($permission, GuardEnum::STANDARD->value)) {
            throw new AuthorizationException();
        }
    }

    protected function pageSize(Request $request): int
    {
        $size = (int)$request->input('page.size', config('api.pagination.default_size'));

        return max(1, min($size, (int)config('api.pagination.max_size')));
    }

    protected function pageNumber(Request $request): int
    {
        return max(1, (int)$request->input('page.number', 1));
    }

    protected function paginated(AnonymousResourceCollection $collection): JsonResponse
    {
        $paginator = $collection->resource;

        return response()->json([
            'data' => $collection->resolve(),
            'meta' => [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'total_pages' => $paginator->lastPage(),
                ],
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    protected function noContent(): Response
    {
        return response()->noContent();
    }
}
```

- [ ] **Step 5: Tests laufen lassen — erwartet PASS**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/V1/ApiErrorHandlingTest.php`
Expected: PASS. Hinweis: Falls `l5-swagger`-Publish weitere Dateien anlegt (`config/l5-swagger.php`),
bleiben sie in diesem Task unkonfiguriert — Task 8 übernimmt.

- [ ] **Step 6: Commit**

```bash
git add composer.json composer.lock config/api.php config/l5-swagger.php bootstrap/app.php \
  app/Http/Controllers/Api/V1/ApiController.php .gitignore tests/Feature/Http/Api/V1/ApiErrorHandlingTest.php
git commit -m "feat(api): add api base controller, scope middleware aliases and json error rendering (#250)"
```

---

### Task 2: Scope-Taxonomie-Seeder & BatchTypeEnum::API

**Files:**
- Create: `database/seeders/PassportScopeTaxonomySeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `app/Enum/BatchTypeEnum.php`
- Test: `tests/Integration/Seeders/PassportScopeTaxonomySeederTest.php`

**Interfaces:**
- Consumes: `N3XT0R\LaravelPassportAuthorizationCore\Models\PassportScopeResource` (fillable:
  `name`, `description`, `is_active`), `PassportScopeAction` (fillable: `name`, `description`,
  `resource_id`, `is_active`). Scope-Name = `<resource>:<action>` (`ScopeName.php:23`);
  Actions mit `resource_id = null` sind GLOBAL und kombinieren mit jeder Resource
  (`ScopeRegistryService::actionsForResource`). `passport_scope_actions.name` ist global unique —
  deshalb globale Actions verwenden, KEINE per-Resource-Duplikate versuchen.
- Produces: 4 Resources (`videos`, `channels`, `offers`, `teams`) + 3 globale Actions (`read`,
  `write`, `delete`, jeweils `resource_id = null`) → 12 Scopes. `channels:delete` und
  `offers:delete` existieren dadurch ohne Routen — gewollt/harmlos, kein Endpoint prüft sie.
- Produces: `BatchTypeEnum::API = 'api'`.

- [ ] **Step 1: Failing Test schreiben**

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Seeders;

use Database\Seeders\PassportScopeTaxonomySeeder;
use N3XT0R\LaravelPassportAuthorizationCore\Models\PassportScopeAction;
use N3XT0R\LaravelPassportAuthorizationCore\Models\PassportScopeResource;
use N3XT0R\LaravelPassportAuthorizationCore\Services\Scopes\ScopeRegistryService;
use Tests\DatabaseTestCase;

final class PassportScopeTaxonomySeederTest extends DatabaseTestCase
{
    public function testSeederCreatesFullTaxonomy(): void
    {
        $this->seed(PassportScopeTaxonomySeeder::class);

        $scopes = app(ScopeRegistryService::class)->allScopeNames()->pluck('scope');
        $expected = [
            'videos:read', 'videos:write', 'videos:delete',
            'channels:read', 'channels:write',
            'offers:read', 'offers:write',
            'teams:read', 'teams:write', 'teams:delete',
        ];
        foreach ($expected as $scope) {
            $this->assertContains($scope, $scopes->all(), "missing scope {$scope}");
        }
    }

    public function testSeederIsIdempotent(): void
    {
        $this->seed(PassportScopeTaxonomySeeder::class);
        $this->seed(PassportScopeTaxonomySeeder::class);

        $this->assertSame(4, PassportScopeResource::query()->count());
        $this->assertSame(3, PassportScopeAction::query()->count());
    }
}
```

- [ ] **Step 2: Test laufen lassen — erwartet FAIL**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Seeders/PassportScopeTaxonomySeederTest.php`
Expected: FAIL (`Class PassportScopeTaxonomySeeder not found`).

- [ ] **Step 3: Seeder implementieren**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use N3XT0R\LaravelPassportAuthorizationCore\Models\PassportScopeAction;
use N3XT0R\LaravelPassportAuthorizationCore\Models\PassportScopeResource;
use N3XT0R\LaravelPassportAuthorizationCore\Services\Scopes\ScopeRegistryService;

class PassportScopeTaxonomySeeder extends Seeder
{
    private const array RESOURCES = ['videos', 'channels', 'offers', 'teams'];

    private const array GLOBAL_ACTIONS = ['read', 'write', 'delete'];

    public function run(): void
    {
        foreach (self::RESOURCES as $resourceName) {
            PassportScopeResource::query()->firstOrCreate(
                ['name' => $resourceName],
                ['description' => ucfirst($resourceName) . ' REST API resource', 'is_active' => true],
            );
        }

        foreach (self::GLOBAL_ACTIONS as $actionName) {
            PassportScopeAction::query()->firstOrCreate(
                ['name' => $actionName],
                [
                    'description' => ucfirst($actionName) . ' access',
                    'resource_id' => null,
                    'is_active' => true,
                ],
            );
        }

        app(ScopeRegistryService::class)->clearCache();
    }
}
```

In `DatabaseSeeder::run()` die Klasse ans Ende der `$this->call([...])`-Liste anhängen.
In `BatchTypeEnum` ergänzen: `case API = 'api';`.

- [ ] **Step 4: Test laufen lassen — erwartet PASS (oder Eskalation)**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Seeders/PassportScopeTaxonomySeederTest.php`
Expected: PASS.

- [ ] **Step 5: Regression Standard-Suite**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api`
Expected: PASS (Taxonomie-Seeding in DatabaseSeeder darf nichts brechen).

- [ ] **Step 6: Commit**

```bash
git add database/seeders/PassportScopeTaxonomySeeder.php database/seeders/DatabaseSeeder.php \
  app/Enum/BatchTypeEnum.php tests/Integration/Seeders/PassportScopeTaxonomySeederTest.php
git commit -m "feat(api): seed passport scope taxonomy for rest resources, add api batch type (#250)"
```

---

### Task 3: Videos — Read-Endpoints (index/show)

**Files:**
- Create: `app/Http/Controllers/Api/V1/VideoController.php` (nur index/show; Task 4 erweitert)
- Create: `app/Http/Resources/Api/V1/VideoResource.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Http/Api/V1/VideoReadEndpointsTest.php`

**Interfaces:**
- Consumes: `ApiController` (Task 1), Scopes `videos:read` (Task 2),
  `Video::scopeHasUsersClips(Builder, User)`, `ProcessingStatusEnum`.
- Produces: `VideoResource` (Felder: `id`, `original_name`, `ext`, `bytes`,
  `human_readable_size`, `processing_status` (string-value), `created_at`, `updated_at` —
  ISO8601 via `toIso8601String()`; NIEMALS `path`, `disk`, `hash`).
  Routen-Namen `api.v1.videos.index` / `api.v1.videos.show`.

- [ ] **Step 1: Failing Tests schreiben**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use App\Models\User;
use App\Models\Video;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class VideoReadEndpointsTest extends DatabaseTestCase
{
    private function actingUser(array $scopes = ['videos:read']): User
    {
        $user = User::factory()->withOwnTeam()->standard()->create();
        Passport::actingAs($user, $scopes);

        return $user;
    }

    public function testIndexListsOnlyOwnVideosWithPaginationMeta(): void
    {
        $user = $this->actingUser();
        $own = Video::factory()->withClips(1, $user)->create();
        Video::factory()->create();

        $response = $this->getJson('/api/v1/videos');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->getKey())
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonStructure([
                'data' => [['id', 'original_name', 'ext', 'bytes', 'processing_status', 'created_at']],
                'meta' => ['pagination' => ['current_page', 'per_page', 'total', 'total_pages']],
                'links' => ['first', 'last', 'prev', 'next'],
            ]);
        $this->assertArrayNotHasKey('path', $response->json('data.0'));
        $this->assertArrayNotHasKey('hash', $response->json('data.0'));
    }

    public function testIndexSupportsFilterSortAndPageSize(): void
    {
        $user = $this->actingUser();
        Video::factory()->withClips(1, $user)->create(['original_name' => 'alpha.mp4']);
        Video::factory()->withClips(1, $user)->create(['original_name' => 'beta.mp4']);

        $this->getJson('/api/v1/videos?filter[original_name]=alpha')
            ->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/videos?sort=original_name&page[size]=1&page[number]=2')
            ->assertOk()
            ->assertJsonPath('data.0.original_name', 'beta.mp4')
            ->assertJsonPath('meta.pagination.per_page', 1);
    }

    public function testPageSizeIsCappedAtMax(): void
    {
        $this->actingUser();
        $this->getJson('/api/v1/videos?page[size]=9999')
            ->assertOk()->assertJsonPath('meta.pagination.per_page', 100);
    }

    public function testShowForeignVideoReturns404(): void
    {
        $this->actingUser();
        $foreign = Video::factory()->create();

        $this->getJson('/api/v1/videos/' . $foreign->getKey())->assertNotFound();
    }

    public function testMissingScopeReturns403(): void
    {
        $this->actingUser(['channels:read']);
        $this->getJson('/api/v1/videos')->assertForbidden();
    }

    public function testUnauthenticatedReturns401(): void
    {
        $this->getJson('/api/v1/videos')->assertUnauthorized();
    }
}
```

- [ ] **Step 2: Tests laufen lassen — erwartet FAIL (404-Route)**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/V1/VideoReadEndpointsTest.php`

- [ ] **Step 3: Implementieren**

`app/Http/Resources/Api/V1/VideoResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Video
 */
class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'original_name' => $this->original_name,
            'ext' => $this->ext,
            'bytes' => $this->bytes,
            'human_readable_size' => $this->human_readable_size,
            'processing_status' => $this->processing_status?->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
```

`VideoController` (index/show):

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\VideoResource;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class VideoController extends ApiController
{
    private function visibleVideos(Request $request): Builder
    {
        return Video::query()->hasUsersClips($this->apiUser($request));
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeApi($request, 'ViewAny:Video');

        $videos = QueryBuilder::for($this->visibleVideos($request))
            ->allowedFilters([
                AllowedFilter::partial('original_name'),
                AllowedFilter::exact('processing_status'),
                AllowedFilter::callback(
                    'created_after',
                    static fn (Builder $query, mixed $value) => $query->where('created_at', '>=', $value),
                ),
                AllowedFilter::callback(
                    'created_before',
                    static fn (Builder $query, mixed $value) => $query->where('created_at', '<=', $value),
                ),
            ])
            ->allowedSorts(['original_name', 'created_at', 'bytes'])
            ->defaultSort('-created_at')
            ->paginate(
                perPage: $this->pageSize($request),
                page: $this->pageNumber($request),
            )
            ->appends($request->query());

        return $this->paginated(VideoResource::collection($videos));
    }

    public function show(Request $request, int $video): VideoResource
    {
        $this->authorizeApi($request, 'ViewAny:Video');

        return new VideoResource($this->visibleVideos($request)->findOrFail($video));
    }
}
```

`routes/api.php` — v1-Gruppe ergänzen (bestehende `/api/user`-Route bleibt unverändert):

```php
use App\Http\Controllers\Api\V1\VideoController;

Route::prefix('v1')->name('api.v1.')->middleware('auth:api')->group(function (): void {
    Route::middleware('scope:videos:read')->group(function (): void {
        Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
        Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show');
    });
});
```

Hinweis: bewusst KEIN implizites Route-Model-Binding — `show()` nimmt die ID und `findOrFail()`
läuft durch die Sichtbarkeits-Query (→ 404 für Fremde). Dieses Muster gilt für alle Ressourcen.

- [ ] **Step 4: Tests laufen lassen — erwartet PASS**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/V1/VideoReadEndpointsTest.php`

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/V1/VideoController.php app/Http/Resources/Api/V1/VideoResource.php \
  routes/api.php tests/Feature/Http/Api/V1/VideoReadEndpointsTest.php
git commit -m "feat(api): add video read endpoints with filtering, sorting and pagination (#250)"
```

---

### Task 4: Videos — Write-Endpoints (store/update/destroy)

**Files:**
- Modify: `app/Http/Controllers/Api/V1/VideoController.php`
- Create: `app/Application/Api/UploadVideoUseCase.php`
- Create: `app/Http/Requests/Api/V1/StoreVideoRequest.php`
- Create: `app/Http/Requests/Api/V1/UpdateVideoRequest.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Http/Api/V1/VideoWriteEndpointsTest.php`

**Interfaces:**
- Consumes: `TeamRepository::getDefaultTeamForUser(User): ?Team`,
  `ClipRepository::create(array): Clip`, `VideoService::delete(Video): bool`,
  `App\Events\Video\VideoQueuedForIngest::dispatch(Video $video, ?User $user)`,
  `ProcessingStatusEnum::Pending`, Scopes `videos:write`/`videos:delete`.
- Produces: `UploadVideoUseCase::execute(UploadedFile $file, int $startSec, int $endSec,
  User $user): Video` (Orchestrierung Datei→Video→Clip→Event, per Constructor-Injection
  `TeamRepository`/`ClipRepository`; ADR-0003: Controller bleibt reiner HTTP-Entry).
- Produces: `POST /api/v1/videos` (Multipart: `file`, `clip[start_sec]`, `clip[end_sec]`) → 201 +
  `Location`-Header auf `api.v1.videos.show`; `PATCH` (JSON: `original_name`) → 200;
  `DELETE` → 204.

- [ ] **Step 1: Failing Tests schreiben**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use App\Events\Video\VideoQueuedForIngest;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class VideoWriteEndpointsTest extends DatabaseTestCase
{
    private function actingUser(array $scopes): User
    {
        $user = User::factory()->withOwnTeam()->standard()->create();
        Passport::actingAs($user, $scopes);

        return $user;
    }

    public function testStoreUploadsFileCreatesClipAndDispatchesIngestEvent(): void
    {
        Storage::fake('videos');
        Event::fake([VideoQueuedForIngest::class]);
        $user = $this->actingUser(['videos:write']);

        $response = $this->post('/api/v1/videos', [
            'file' => UploadedFile::fake()->create('dashcam.mp4', 2048, 'video/mp4'),
            'clip' => ['start_sec' => 5, 'end_sec' => 30],
        ], ['Accept' => 'application/json']);

        $response->assertCreated()->assertHeader('Location');
        $video = Video::query()->findOrFail($response->json('data.id'));
        Storage::disk('videos')->assertExists($video->path);
        $this->assertSame('pending', $response->json('data.processing_status'));
        $this->assertSame($user->getKey(), $video->clips()->firstOrFail()->user_id);
        $this->assertSame(5, $video->clips()->firstOrFail()->start_sec);
        Event::assertDispatched(VideoQueuedForIngest::class);
    }

    public function testStoreValidatesFileAndClipTimes(): void
    {
        Storage::fake('videos');
        $this->actingUser(['videos:write']);

        $this->postJson('/api/v1/videos', ['clip' => ['start_sec' => 10, 'end_sec' => 5]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file', 'clip.end_sec']);
    }

    public function testUpdateRenamesOwnVideo(): void
    {
        $user = $this->actingUser(['videos:write']);
        $video = Video::factory()->withClips(1, $user)->create(['original_name' => 'old.mp4']);

        $this->patchJson('/api/v1/videos/' . $video->getKey(), ['original_name' => 'new.mp4'])
            ->assertOk()->assertJsonPath('data.original_name', 'new.mp4');
    }

    public function testUpdateForeignVideoReturns404(): void
    {
        $this->actingUser(['videos:write']);
        $foreign = Video::factory()->create();

        $this->patchJson('/api/v1/videos/' . $foreign->getKey(), ['original_name' => 'x.mp4'])
            ->assertNotFound();
    }

    public function testDestroyDeletesOwnVideo(): void
    {
        Storage::fake('videos');
        $user = $this->actingUser(['videos:delete']);
        $video = Video::factory()->withClips(1, $user)->create();

        $this->deleteJson('/api/v1/videos/' . $video->getKey())->assertNoContent();
    }

    public function testWriteWithReadOnlyScopeReturns403(): void
    {
        $user = $this->actingUser(['videos:read']);
        $video = Video::factory()->withClips(1, $user)->create();

        $this->patchJson('/api/v1/videos/' . $video->getKey(), ['original_name' => 'x.mp4'])
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Tests laufen lassen — erwartet FAIL**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/V1/VideoWriteEndpointsTest.php`

- [ ] **Step 3: Implementieren**

`StoreVideoRequest` (`authorize(): true` — Autorisierung macht der Controller):

```php
public function rules(): array
{
    return [
        'file' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-matroska'],
        'clip' => ['required', 'array'],
        'clip.start_sec' => ['required', 'integer', 'min:0'],
        'clip.end_sec' => ['required', 'integer', 'gt:clip.start_sec'],
    ];
}
```

`UpdateVideoRequest`: `'original_name' => ['required', 'string', 'max:255']`.

`app/Application/Api/UploadVideoUseCase.php` (ADR-0002/0003: Orchestrierung außerhalb des
Controllers):

```php
<?php

declare(strict_types=1);

namespace App\Application\Api;

use App\Enum\ProcessingStatusEnum;
use App\Events\Video\VideoQueuedForIngest;
use App\Models\User;
use App\Models\Video;
use App\Repository\ClipRepository;
use App\Repository\TeamRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadVideoUseCase
{
    public function __construct(
        private readonly TeamRepository $teamRepository,
        private readonly ClipRepository $clipRepository,
    ) {
    }

    /**
     * Store the uploaded file on the videos disk, create the video record with
     * its initial clip and queue it for the ingest pipeline.
     */
    public function execute(UploadedFile $file, int $startSec, int $endSec, User $user): Video
    {
        $path = $file->store('', 'videos');

        $video = Video::query()->create([
            'original_name' => $file->getClientOriginalName(),
            'ext' => strtoupper($file->getClientOriginalExtension()),
            'bytes' => Storage::disk('videos')->size($path),
            'path' => $path,
            'disk' => 'videos',
            'processing_status' => ProcessingStatusEnum::Pending,
            'team_id' => $this->teamRepository->getDefaultTeamForUser($user)?->getKey(),
        ]);

        $this->clipRepository->create([
            'video_id' => $video->getKey(),
            'user_id' => $user->getKey(),
            'submitted_by' => $user->display_name,
            'start_sec' => $startSec,
            'end_sec' => $endSec,
        ]);

        VideoQueuedForIngest::dispatch($video, $user);

        return $video;
    }
}
```

`VideoController` — Methoden ergänzen (Imports entsprechend; UseCase via Method-Injection):

```php
public function store(StoreVideoRequest $request, UploadVideoUseCase $uploadVideo): JsonResponse
{
    $this->authorizeApi($request, 'Create:Video');

    $video = $uploadVideo->execute(
        file: $request->file('file'),
        startSec: (int)$request->validated('clip.start_sec'),
        endSec: (int)$request->validated('clip.end_sec'),
        user: $this->apiUser($request),
    );

    return (new VideoResource($video))
        ->response($request)
        ->setStatusCode(201)
        ->header('Location', route('api.v1.videos.show', ['video' => $video->getKey()]));
}

public function update(UpdateVideoRequest $request, int $video): VideoResource
{
    $this->authorizeApi($request, 'Update:Video');
    $model = $this->visibleVideos($request)->findOrFail($video);
    $model->update($request->validated());

    return new VideoResource($model->refresh());
}

public function destroy(Request $request, int $video): Response
{
    $this->authorizeApi($request, 'Delete:Video');
    $model = $this->visibleVideos($request)->findOrFail($video);
    app(VideoService::class)->delete($model);

    return $this->noContent();
}
```

Routen (innerhalb der v1-Gruppe):

```php
Route::middleware('scope:videos:write')->group(function (): void {
    Route::post('/videos', [VideoController::class, 'store'])->name('videos.store');
    Route::patch('/videos/{video}', [VideoController::class, 'update'])->name('videos.update');
});
Route::delete('/videos/{video}', [VideoController::class, 'destroy'])
    ->middleware('scope:videos:delete')->name('videos.destroy');
```

- [ ] **Step 4: Tests laufen lassen — erwartet PASS**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/V1/VideoWriteEndpointsTest.php`

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/V1/VideoController.php app/Application/Api/UploadVideoUseCase.php \
  app/Http/Requests/Api/V1/ routes/api.php tests/Feature/Http/Api/V1/VideoWriteEndpointsTest.php
git commit -m "feat(api): add video upload, rename and delete endpoints reusing ingest pipeline (#250)"
```

---

### Task 5: Channels — Endpoints (index/show/update)

**Files:**
- Create: `app/Http/Controllers/Api/V1/ChannelController.php`
- Create: `app/Http/Resources/Api/V1/ChannelResource.php`
- Create: `app/Http/Requests/Api/V1/UpdateChannelRequest.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Http/Api/V1/ChannelEndpointsTest.php`

**Interfaces:**
- Consumes: `Channel::scopeUserHasAccess(Builder, User)` (prüft `channelUsers`-Pivot),
  Scopes `channels:read`/`channels:write`, Permission `ManageChannels:Team`.
- Produces: `ChannelResource` (Felder: `id`, `name`, `creator_name`, `email`, `youtube_name`,
  `is_video_reception_paused`, `created_at`, `updated_at`; NICHT `weight`, `weekly_quota`,
  `approved_at`). Routen `api.v1.channels.index|show|update`.

- [ ] **Step 1: Failing Tests schreiben**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use App\Models\Channel;
use App\Models\User;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class ChannelEndpointsTest extends DatabaseTestCase
{
    private function actingUserWithChannel(array $scopes): array
    {
        $user = User::factory()->withOwnTeam()->standard()->create();
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user->getKey(), ['is_user_verified' => true]);
        Passport::actingAs($user, $scopes);

        return [$user, $channel];
    }

    public function testIndexListsOnlyAccessibleChannels(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:read']);
        Channel::factory()->create();

        $this->getJson('/api/v1/channels')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $channel->getKey());
    }

    public function testShowHidesAdminFields(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:read']);

        $json = $this->getJson('/api/v1/channels/' . $channel->getKey())
            ->assertOk()->json('data');
        $this->assertArrayNotHasKey('weight', $json);
        $this->assertArrayNotHasKey('weekly_quota', $json);
    }

    public function testUpdateTogglesVideoReceptionPause(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:write']);

        $this->patchJson('/api/v1/channels/' . $channel->getKey(), [
            'is_video_reception_paused' => true,
        ])->assertOk()->assertJsonPath('data.is_video_reception_paused', true);
    }

    public function testUpdateRejectsAdminFields(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:write']);

        $this->patchJson('/api/v1/channels/' . $channel->getKey(), ['weight' => 99])
            ->assertUnprocessable();
        $this->assertNotSame(99, $channel->refresh()->weight);
    }

    public function testForeignChannelReturns404(): void
    {
        $this->actingUserWithChannel(['channels:read', 'channels:write']);
        $foreign = Channel::factory()->create();

        $this->getJson('/api/v1/channels/' . $foreign->getKey())->assertNotFound();
    }
}
```

- [ ] **Step 2: Tests laufen lassen — erwartet FAIL**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/V1/ChannelEndpointsTest.php`

- [ ] **Step 3: Implementieren**

`app/Http/Resources/Api/V1/ChannelResource.php` (Aufbau wie `VideoResource` aus Task 3):
Felder `id`, `name`, `creator_name`, `email`, `youtube_name`, `is_video_reception_paused`
(bool), `created_at`/`updated_at` als `toIso8601String()`.

`ChannelController`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\UpdateChannelRequest;
use App\Http\Resources\Api\V1\ChannelResource;
use App\Models\Channel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ChannelController extends ApiController
{
    private function visibleChannels(Request $request): Builder
    {
        return Channel::query()->userHasAccess($this->apiUser($request));
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeApi($request, 'ManageChannels:Team');

        $channels = QueryBuilder::for($this->visibleChannels($request))
            ->allowedFilters([AllowedFilter::exact('is_video_reception_paused')])
            ->allowedSorts(['name', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate(perPage: $this->pageSize($request), page: $this->pageNumber($request))
            ->appends($request->query());

        return $this->paginated(ChannelResource::collection($channels));
    }

    public function show(Request $request, int $channel): ChannelResource
    {
        $this->authorizeApi($request, 'ManageChannels:Team');

        return new ChannelResource($this->visibleChannels($request)->findOrFail($channel));
    }

    public function update(UpdateChannelRequest $request, int $channel): ChannelResource
    {
        $this->authorizeApi($request, 'ManageChannels:Team');
        $model = $this->visibleChannels($request)->findOrFail($channel);
        $model->update($request->validated());

        return new ChannelResource($model->refresh());
    }
}
```

`UpdateChannelRequest`-Rules — unbekannte Felder hart ablehnen, damit Admin-Felder nicht per
Mass-Assignment durchrutschen:

```php
public function rules(): array
{
    return ['is_video_reception_paused' => ['required', 'boolean']];
}

protected function prepareForValidation(): void
{
    $unknown = array_diff(array_keys($this->all()), ['is_video_reception_paused']);
    if ($unknown !== []) {
        throw ValidationException::withMessages(
            array_fill_keys($unknown, 'This field cannot be updated via the API.'),
        );
    }
}
```

Routen: GET-Routen unter `scope:channels:read`, PATCH unter `scope:channels:write`.

- [ ] **Step 4: Tests laufen lassen — erwartet PASS**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/V1/ChannelEndpointsTest.php`

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/V1/ChannelController.php app/Http/Resources/Api/V1/ChannelResource.php \
  app/Http/Requests/Api/V1/UpdateChannelRequest.php routes/api.php tests/Feature/Http/Api/V1/ChannelEndpointsTest.php
git commit -m "feat(api): add channel endpoints with operator-scoped visibility (#250)"
```

---

### Task 6: Offers — Endpoints (index/show/store/comment)

**Files:**
- Create: `app/Http/Controllers/Api/V1/OfferController.php`
- Create: `app/Application/Api/CreateOfferUseCase.php`
- Create: `app/Http/Resources/Api/V1/OfferResource.php`
- Create: `app/Http/Requests/Api/V1/StoreOfferRequest.php`
- Create: `app/Http/Requests/Api/V1/StoreOfferCommentRequest.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Http/Api/V1/OfferEndpointsTest.php`

**Interfaces:**
- Consumes: `Assignment` (fillable inkl. `video_id`, `channel_id`, `batch_id`, `note`;
  `setExpiresAt(?int $ttlDays = null)`), `BatchService::startBatch(BatchTypeEnum): Batch`,
  `BatchTypeEnum::API` (Task 2), Scopes `offers:read`/`offers:write`, Permission `View:MyOffers`
  (Rolle `channel_operator`), `Assignment::scopeHasUsersClips(Builder, User)`,
  `Channel::scopeUserHasAccess(Builder, User)`.
- Produces: `OfferResource` (Felder: `id`, `status`, `video_id`, `channel_id`, `note`,
  `expires_at`, `created_at`; NICHT `download_token`). Routen
  `api.v1.offers.index|show|store|comment`. Sichtbarkeit: Assignment gehört zu einem für den
  User zugänglichen Channel ODER zu einem Video mit eigenen Clips.

- [ ] **Step 1: Failing Tests schreiben**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\User;
use App\Models\Video;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class OfferEndpointsTest extends DatabaseTestCase
{
    private function actingOperator(array $scopes): array
    {
        $user = User::factory()->withOwnTeam()->standard()
            ->withRole(RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD->value)->create();
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user->getKey(), ['is_user_verified' => true]);
        Passport::actingAs($user, $scopes);

        return [$user, $channel];
    }

    public function testIndexShowsOffersOfOwnChannelOnly(): void
    {
        [, $channel] = $this->actingOperator(['offers:read']);
        $own = Assignment::factory()->create(['channel_id' => $channel->getKey()]);
        Assignment::factory()->create();

        $this->getJson('/api/v1/offers')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->getKey());
    }

    public function testOfferResponseNeverContainsDownloadToken(): void
    {
        [, $channel] = $this->actingOperator(['offers:read']);
        $offer = Assignment::factory()->create(['channel_id' => $channel->getKey()]);

        $json = $this->getJson('/api/v1/offers/' . $offer->getKey())->assertOk()->json('data');
        $this->assertArrayNotHasKey('download_token', $json);
    }

    public function testStoreCreatesOfferWithApiBatchAndExpiry(): void
    {
        [$user, $channel] = $this->actingOperator(['offers:write']);
        $video = Video::factory()->withClips(1, $user)->create();

        $response = $this->postJson('/api/v1/offers', [
            'video_id' => $video->getKey(),
            'channel_id' => $channel->getKey(),
        ]);

        $response->assertCreated();
        $offer = Assignment::query()->findOrFail($response->json('data.id'));
        $this->assertSame('api', $offer->batch->type);
        $this->assertSame('queued', $offer->status);
        $this->assertNotNull($offer->expires_at);
    }

    public function testStoreRejectsInvisibleVideoOrChannel(): void
    {
        $this->actingOperator(['offers:write']);
        $foreignVideo = Video::factory()->create();
        $foreignChannel = Channel::factory()->create();

        $this->postJson('/api/v1/offers', [
            'video_id' => $foreignVideo->getKey(),
            'channel_id' => $foreignChannel->getKey(),
        ])->assertUnprocessable()
          ->assertJsonValidationErrors(['video_id', 'channel_id']);
    }

    public function testCommentSetsNote(): void
    {
        [, $channel] = $this->actingOperator(['offers:write']);
        $offer = Assignment::factory()->create(['channel_id' => $channel->getKey()]);

        $this->postJson('/api/v1/offers/' . $offer->getKey() . '/comment', ['note' => 'great clip'])
            ->assertOk()->assertJsonPath('data.note', 'great clip');
    }

    public function testStatusIsNotWritableViaStore(): void
    {
        [$user, $channel] = $this->actingOperator(['offers:write']);
        $video = Video::factory()->withClips(1, $user)->create();

        $response = $this->postJson('/api/v1/offers', [
            'video_id' => $video->getKey(),
            'channel_id' => $channel->getKey(),
            'status' => 'picked_up',
        ]);

        $response->assertUnprocessable();
    }
}
```

- [ ] **Step 2: Tests laufen lassen — erwartet FAIL**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/V1/OfferEndpointsTest.php`

- [ ] **Step 3: Implementieren**

`OfferResource` (Aufbau wie `VideoResource` aus Task 3): Felder `id`, `status`, `video_id`,
`channel_id`, `note`, `expires_at` (`toIso8601String()`), `created_at` (`toIso8601String()`).

`OfferController::visibleOffers()`:

```php
private function visibleOffers(Request $request): Builder
{
    $user = $this->apiUser($request);

    return Assignment::query()->where(function (Builder $query) use ($user): void {
        $query->whereHas('channel', static function (Builder $channel) use ($user): void {
            $channel->userHasAccess($user);
        })->orWhere(static function (Builder $inner) use ($user): void {
            $inner->hasUsersClips($user);
        });
    });
}
```

`index`/`show`:

```php
public function index(Request $request): JsonResponse
{
    $this->authorizeApi($request, 'View:MyOffers');

    $offers = QueryBuilder::for($this->visibleOffers($request))
        ->allowedFilters([
            AllowedFilter::exact('status'),
            AllowedFilter::exact('channel_id'),
        ])
        ->allowedSorts(['created_at', 'expires_at'])
        ->defaultSort('-created_at')
        ->paginate(perPage: $this->pageSize($request), page: $this->pageNumber($request))
        ->appends($request->query());

    return $this->paginated(OfferResource::collection($offers));
}

public function show(Request $request, int $offer): OfferResource
{
    $this->authorizeApi($request, 'View:MyOffers');

    return new OfferResource($this->visibleOffers($request)->findOrFail($offer));
}
```

`StoreOfferRequest`: Rules `'video_id' => ['required', 'integer']`,
`'channel_id' => ['required', 'integer']`; unbekannte Felder (insbesondere `status`) wie in
Task 5 via `prepareForValidation` mit `ValidationException::withMessages` ablehnen.
Existenz+Sichtbarkeit prüft ein `after()`-Hook im FormRequest: `video_id` muss in
`Video::query()->hasUsersClips($user)` existieren, `channel_id` in
`Channel::query()->userHasAccess($user)` (User via `$this->user('api')`); sonst
Validation-Error auf das jeweilige Feld. `store`/`comment` im Controller:

`app/Application/Api/CreateOfferUseCase.php` (ADR-0003):

```php
<?php

declare(strict_types=1);

namespace App\Application\Api;

use App\Enum\BatchTypeEnum;
use App\Models\Assignment;
use App\Services\BatchService;

class CreateOfferUseCase
{
    public function __construct(private readonly BatchService $batchService)
    {
    }

    /**
     * Create a manual API offer: wraps the assignment in a fresh batch of type
     * "api" and applies the default expiry TTL.
     */
    public function execute(int $videoId, int $channelId): Assignment
    {
        $batch = $this->batchService->startBatch(BatchTypeEnum::API);

        $offer = new Assignment([
            'video_id' => $videoId,
            'channel_id' => $channelId,
            'batch_id' => $batch->getKey(),
        ]);
        $offer->setExpiresAt();
        $offer->save();

        return $offer;
    }
}
```

```php
public function store(StoreOfferRequest $request, CreateOfferUseCase $createOffer): JsonResponse
{
    $this->authorizeApi($request, 'View:MyOffers');

    $offer = $createOffer->execute(
        videoId: (int)$request->validated('video_id'),
        channelId: (int)$request->validated('channel_id'),
    );

    return (new OfferResource($offer))
        ->response($request)
        ->setStatusCode(201)
        ->header('Location', route('api.v1.offers.show', ['offer' => $offer->getKey()]));
}

public function comment(StoreOfferCommentRequest $request, int $offer): OfferResource
{
    $this->authorizeApi($request, 'View:MyOffers');
    $model = $this->visibleOffers($request)->findOrFail($offer);
    $model->update(['note' => $request->validated('note')]);

    return new OfferResource($model->refresh());
}
```

`StoreOfferCommentRequest`: `'note' => ['required', 'string', 'max:1000']`.
Routen: GETs unter `scope:offers:read`; `POST /offers` und `POST /offers/{offer}/comment` unter
`scope:offers:write`.

- [ ] **Step 4: Tests laufen lassen — erwartet PASS**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/V1/OfferEndpointsTest.php`

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/V1/OfferController.php app/Application/Api/CreateOfferUseCase.php \
  app/Http/Resources/Api/V1/OfferResource.php app/Http/Requests/Api/V1/StoreOfferRequest.php \
  app/Http/Requests/Api/V1/StoreOfferCommentRequest.php routes/api.php \
  tests/Feature/Http/Api/V1/OfferEndpointsTest.php
git commit -m "feat(api): add offer endpoints with api batch type and comment support (#250)"
```

---

### Task 7: Teams — Endpoints (index/show/store/destroy)

**Files:**
- Create: `app/Http/Controllers/Api/V1/TeamController.php`
- Create: `app/Http/Resources/Api/V1/TeamResource.php`
- Create: `app/Http/Requests/Api/V1/StoreTeamRequest.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Http/Api/V1/TeamEndpointsTest.php`

**Interfaces:**
- Consumes: `Team` (fillable `name`, `slug`, `owner_id`; Relation `users()`), Scopes
  `teams:read|write|delete`. Keine Spatie-Permission — Ownership entscheidet.
- Produces: `TeamResource` (Felder: `id`, `name`, `slug`, `owner_id`, `created_at`).
  Sichtbarkeit: `owner_id` = User ODER Mitglied via `users()`-Pivot. Routen
  `api.v1.teams.index|show|store|destroy`.

- [ ] **Step 1: Failing Tests schreiben**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use App\Models\Team;
use App\Models\User;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class TeamEndpointsTest extends DatabaseTestCase
{
    private function actingUser(array $scopes): User
    {
        $user = User::factory()->withOwnTeam()->standard()->create();
        Passport::actingAs($user, $scopes);

        return $user;
    }

    public function testIndexShowsOwnAndMemberTeamsOnly(): void
    {
        $user = $this->actingUser(['teams:read']);
        $memberTeam = Team::factory()->create();
        $memberTeam->users()->attach($user->getKey());
        Team::factory()->create();

        $ids = $this->getJson('/api/v1/teams')->assertOk()->json('data.*.id');
        $ownTeamId = Team::query()->where('owner_id', $user->getKey())->firstOrFail()->getKey();
        $this->assertContains($memberTeam->getKey(), $ids);
        $this->assertContains($ownTeamId, $ids);
        $this->assertCount(2, $ids);
    }

    public function testStoreCreatesTeamOwnedByUser(): void
    {
        $user = $this->actingUser(['teams:write']);

        $response = $this->postJson('/api/v1/teams', ['name' => 'API Crew']);

        $response->assertCreated()->assertJsonPath('data.name', 'API Crew');
        $this->assertSame($user->getKey(), Team::query()->findOrFail($response->json('data.id'))->owner_id);
    }

    public function testDestroyByNonOwnerReturns404(): void
    {
        $user = $this->actingUser(['teams:delete']);
        $memberTeam = Team::factory()->create();
        $memberTeam->users()->attach($user->getKey());

        $this->deleteJson('/api/v1/teams/' . $memberTeam->getKey())->assertNotFound();
    }

    public function testDestroyOwnTeamReturns204(): void
    {
        $user = $this->actingUser(['teams:delete']);
        $team = Team::factory()->create(['owner_id' => $user->getKey()]);

        $this->deleteJson('/api/v1/teams/' . $team->getKey())->assertNoContent();
        $this->assertNull(Team::query()->find($team->getKey()));
    }
}
```

- [ ] **Step 2: Tests laufen lassen — erwartet FAIL**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/V1/TeamEndpointsTest.php`

- [ ] **Step 3: Implementieren**

`TeamResource` (Aufbau wie `VideoResource` aus Task 3): Felder `id`, `name`, `slug`, `owner_id`,
`created_at` (`toIso8601String()`).

`TeamController`:

```php
private function visibleTeams(Request $request): Builder
{
    $user = $this->apiUser($request);

    return Team::query()->where(function (Builder $query) use ($user): void {
        $query->where('owner_id', $user->getKey())
            ->orWhereHas('users', static function (Builder $member) use ($user): void {
                $member->whereKey($user->getKey());
            });
    });
}

public function store(StoreTeamRequest $request): JsonResponse
{
    $user = $this->apiUser($request);
    $team = Team::query()->create([
        'name' => $request->validated('name'),
        'slug' => Str::slug($request->validated('name')) . '-' . Str::lower(Str::random(6)),
        'owner_id' => $user->getKey(),
    ]);
    $team->users()->attach($user->getKey());

    return (new TeamResource($team))
        ->response($request)
        ->setStatusCode(201)
        ->header('Location', route('api.v1.teams.show', ['team' => $team->getKey()]));
}

public function destroy(Request $request, int $team): Response
{
    $model = $this->visibleTeams($request)
        ->where('owner_id', $this->apiUser($request)->getKey())
        ->findOrFail($team);
    $model->delete();

    return $this->noContent();
}
```

`index`/`show`:

```php
public function index(Request $request): JsonResponse
{
    $teams = QueryBuilder::for($this->visibleTeams($request))
        ->allowedFilters([AllowedFilter::partial('name')])
        ->allowedSorts(['name', 'created_at'])
        ->defaultSort('-created_at')
        ->paginate(perPage: $this->pageSize($request), page: $this->pageNumber($request))
        ->appends($request->query());

    return $this->paginated(TeamResource::collection($teams));
}

public function show(Request $request, int $team): TeamResource
{
    return new TeamResource($this->visibleTeams($request)->findOrFail($team));
}
```

`StoreTeamRequest`: `'name' => ['required', 'string', 'max:255']`.
Routen: GETs unter `scope:teams:read`, POST unter `scope:teams:write`, DELETE unter
`scope:teams:delete`.

- [ ] **Step 4: Tests laufen lassen — erwartet PASS**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/V1/TeamEndpointsTest.php`

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/V1/TeamController.php app/Http/Resources/Api/V1/TeamResource.php \
  app/Http/Requests/Api/V1/StoreTeamRequest.php routes/api.php tests/Feature/Http/Api/V1/TeamEndpointsTest.php
git commit -m "feat(api): add team endpoints with owner-based authorization (#250)"
```

---

### Task 8: OpenAPI-Dokumentation, Changelog & Gesamtregression

**Files:**
- Create: `app/OpenApi/OpenApiSpec.php`
- Create: `app/OpenApi/Schemas/VideoSchema.php`, `ChannelSchema.php`, `OfferSchema.php`,
  `TeamSchema.php`, `PaginationMetaSchema.php`, `ValidationErrorSchema.php`
- Modify: alle 4 Controller unter `app/Http/Controllers/Api/V1/` (Attribute)
- Modify: `config/l5-swagger.php` (Titel, `docs` output path bleibt Default `storage/api-docs`)
- Modify: `CHANGELOG.md`
- Test: `tests/Feature/Http/Api/V1/OpenApiDocumentationTest.php`

**Interfaces:**
- Consumes: alle Routen/Resources aus Tasks 3–7.
- Produces: `GET /api/documentation` (Swagger UI, 200) und valide generierte Spec.

- [ ] **Step 1: Failing Test schreiben**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use Illuminate\Support\Facades\Artisan;
use Tests\DatabaseTestCase;

final class OpenApiDocumentationTest extends DatabaseTestCase
{
    public function testOpenApiSpecGeneratesWithoutErrors(): void
    {
        $this->assertSame(0, Artisan::call('l5-swagger:generate'));

        $spec = json_decode(
            (string)file_get_contents(storage_path('api-docs/api-docs.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertSame('3.0.0', $spec['openapi']);
        foreach (['/api/v1/videos', '/api/v1/channels', '/api/v1/offers', '/api/v1/teams'] as $path) {
            $this->assertArrayHasKey($path, $spec['paths'], "missing path {$path}");
        }
        $this->assertArrayHasKey('Video', $spec['components']['schemas']);
        $this->assertArrayHasKey('PaginationMeta', $spec['components']['schemas']);
    }

    public function testSwaggerUiIsReachable(): void
    {
        Artisan::call('l5-swagger:generate');
        $this->get('/api/documentation')->assertOk();
    }
}
```

- [ ] **Step 2: Test laufen lassen — erwartet FAIL**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/V1/OpenApiDocumentationTest.php`
Expected: FAIL (keine Annotationen → Generator-Fehler "Required @OA\Info() not found").

- [ ] **Step 3: Implementieren**

`app/OpenApi/OpenApiSpec.php`:

```php
<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(version: '1.0.0', title: 'Dashclip Delivery API')]
#[OA\Server(url: '/api/v1', description: 'REST API v1')]
#[OA\SecurityScheme(
    securityScheme: 'passport',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'OAuth2 access token issued by Laravel Passport',
)]
final class OpenApiSpec
{
}
```

Schema-Beispiel (`VideoSchema.php`; die anderen analog zu den Resource-Feldern aus Tasks 3–7):

```php
<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Video',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'original_name', type: 'string'),
        new OA\Property(property: 'ext', type: 'string'),
        new OA\Property(property: 'bytes', type: 'integer'),
        new OA\Property(property: 'human_readable_size', type: 'string'),
        new OA\Property(
            property: 'processing_status',
            type: 'string',
            enum: ['pending', 'running', 'completed', 'failed', 'deleted'],
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
final class VideoSchema
{
}
```

`PaginationMetaSchema`: Objekt `pagination` mit `current_page`, `per_page`, `total`,
`total_pages` (alle integer). `ValidationErrorSchema`: `message` (string), `errors` (object,
additionalProperties: array of strings).

Controller-Attribute: pro Endpoint-Methode ein `#[OA\Get|Post|Patch|Delete]` mit `path`,
`security: [['passport' => ['<scope>']]]`, Parametern (`filter[...]`, `sort`, `page[number]`,
`page[size]` bei index; Path-Parameter bei show/update/destroy), Request-Bodies (bei POST/PATCH)
und Responses (200/201/204 mit `ref: '#/components/schemas/...'`, 401, 403, 404, 422 mit
`ValidationError`). `config/l5-swagger.php`: `'title' => 'Dashclip Delivery API'`; sonst
Defaults. Prüfen, dass `scan`-Pfad `app/` abdeckt (Default `base_path('app')` — passt).

- [ ] **Step 4: Tests laufen lassen — erwartet PASS**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/V1/OpenApiDocumentationTest.php`

- [ ] **Step 5: Changelog ergänzen**

Unter `## [Unreleased]` → `### Added` (Zeilen ≤120, Stil wie Bestand):

```markdown
- **REST API core resources (v1)**
    - new authenticated REST endpoints under `/api/v1` for videos (list, detail, multipart
      upload through the existing ingest pipeline, rename, delete), channels (list, detail,
      pause/resume video reception), offers (list, detail, create, comment) and teams
      (list, detail, create, delete), all scoped to the data the authenticated user already
      sees in the Standard panel.
    - filtering (`filter[...]`), sorting (`sort=`) and pagination (`page[number]`,
      `page[size]`, `meta.pagination` response block) on all list endpoints.
    - OAuth2 scope taxonomy (`videos:*`, `channels:*`, `offers:*`, `teams:*`) seeded for the
      self-service client UI; per-route scope enforcement via Passport middleware.
    - interactive OpenAPI 3.0 documentation (l5-swagger) available at `/api/documentation`.
```

- [ ] **Step 6: Volle Regression + Pint**

```bash
docker exec dashclip-delivery-sharing-1 ./vendor/bin/pint --dirty --test
docker exec dashclip-delivery-sharing-1 php artisan test --parallel
```

Expected: Pint clean (sonst `--dirty` ohne `--test` ausführen und Ergebnis committen), volle
Suite grün.

- [ ] **Step 7: Commit**

```bash
git add app/OpenApi/ app/Http/Controllers/Api/V1/ config/l5-swagger.php CHANGELOG.md \
  tests/Feature/Http/Api/V1/OpenApiDocumentationTest.php
git commit -m "feat(api): add openapi 3.0 documentation via l5-swagger and changelog entry (#250)"
```
