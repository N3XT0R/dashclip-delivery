# REST API — OAuth2-Fundament Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Passport-OAuth2 Ende-zu-Ende einsatzbereit machen (Bearer-Token-Auth funktioniert) und
Standard-Panel-Usern erlauben, sich selbst OAuth-Clients/Tokens mit ihren zugewiesenen Scopes
anzulegen, ohne die Clients/Tokens anderer User zu sehen.

**Architecture:** Zwei Repos sind betroffen. `dashclip-delivery` bekommt die App-seitige
Passport-Kernkonfiguration (User-Model, `api`-Guard, Grant-Type-Einschränkung, ein
Sanity-Check-Endpoint) sowie am Ende die Panel-Registrierung. `filament-passport-ui`
(`~/PhpstormProjects/filament-passport-ui`, eigenes Paket-Repo) bekommt einen neuen
"Self-Service"-Modus, der `ClientResource`/`TokenResource` auf den eingeloggten Owner scoped, plus
einen Bugfix für einen falschen Config-Namespace. Die App-seitige Panel-Registrierung (Task 6) kann
erst erfolgen, nachdem die Paket-Änderung (Tasks 4–5) vom User committed, gepusht und getaggt wurde
(etablierter Workflow in diesem Projekt) — Task 6 startet daher mit einem expliziten Check.

**Tech Stack:** Laravel 13, Filament v4, Laravel Passport ^13.0, `n3xt0r/filament-passport-ui`
^2.3, `n3xt0r/laravel-passport-authorization-core` (aktuell 1.3.2), PHPUnit 12 / ParaTest.

**Spec:** `docs/superpowers/specs/2026-08-20-rest-api-oauth2-foundation-design.md`

## Global Constraints

- Test-Runner (dashclip-delivery): `docker exec dashclip-delivery-sharing-1 php artisan test --parallel <path>` — niemals ohne `--parallel`, Host-PHP hat kein mbstring.
- Test-Runner (filament-passport-ui): `docker run --rm -v ~/PhpstormProjects/filament-passport-ui:/app -w /app composer:2 sh -c "vendor/bin/phpunit --no-coverage <args>"` — der paketeigene `app.Dockerfile` schlägt an einem `qlty`-Installer-Netzwerkschritt fehl, deshalb das offizielle `composer:2`-Image statt `docker-compose`.
- CHANGELOG-Einträge (beide Repos) unter `## [Unreleased]`, auf Englisch (ADR 0006 in dashclip-delivery; gleiches Format in filament-passport-ui).
- Commit-Messages enden mit `Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>`.
- `allowed_grant_types` (Sub-Projekt-1-Scope): nur `authorization_code`, `client_credentials`, `personal_access`, `device` — `password` und `implicit` bleiben ausgeschlossen (Legacy-Grants, Security-Kriterium aus Ticket #250).
- Self-Service-Panel (Standard) sieht **ausschließlich eigene** Clients/Tokens, unabhängig von Rolle — keine rollenbasierte Fremdeinsicht (siehe Spec "Out of Scope").
- Package-Repo-Tasks (4–5) enden mit einem lokalen Commit. **Push, Tag und Release macht der User manuell** (etabliertes Muster dieser Session) — der Implementierer von Task 4/5 fragt am Ende nicht nach Push, sondern meldet nur "committed, bereit für Push/Tag".

---

## File Structure

**`dashclip-delivery`:**
- Modify: `app/Models/User.php` — Passport-Interfaces/Traits
- Modify: `config/auth.php` — `api`-Guard
- Create: `config/passport-authorization-core.php` (publiziert + überschrieben) — `allowed_grant_types`
- Modify: `app/Providers/AppServiceProvider.php` — Token-Lifetimes
- Create: `routes/api.php` — Sanity-Check-Route
- Modify: `bootstrap/app.php` — `api`-Routing registrieren
- Create: `tests/Feature/Http/Api/AuthenticatedUserEndpointTest.php`
- Modify: `tests/Integration/Models/UserTest.php` — neue Assertions
- Modify: `app/Providers/Filament/AdminPanelProvider.php` — Plugin registrieren (voll)
- Modify: `app/Providers/Filament/PanelUserPanelProvider.php` — Plugin registrieren (self-service)
- Modify: `composer.json` / `composer.lock` — neue `filament-passport-ui`-Version
- Create: `tests/Feature/Filament/Standard/Resources/PassportSelfServiceTest.php`
- Modify: `CHANGELOG.md`

**`filament-passport-ui`** (`~/PhpstormProjects/filament-passport-ui`):
- Modify: `src/FilamentPassportUiPlugin.php` — Config-Namespace-Fix + `selfService()`/`isSelfService()`
- Modify: `config/passport-ui.php` — `enable_scopes_management`-Key ergänzen
- Modify: `src/Resources/ClientResource.php` — `getEloquentQuery()`
- Modify: `src/Resources/TokenResource.php` — `getEloquentQuery()`
- Modify: `src/Resources/ClientResource/Schemas/ClientWizardForm.php` — OwnerSelect disabled+defaulted im Self-Service-Modus
- Modify: `src/Resources/ClientResource/Pages/CreateClient.php` — Owner serverseitig erzwingen
- Create/Modify: `tests/Feature/FilamentPassportUiPluginTest.php`
- Create/Modify: `tests/Feature/Resources/ClientResourceTest.php`
- Create/Modify: `tests/Feature/Resources/TokenResourceTest.php`
- Create/Modify: `tests/Feature/Resources/ClientResource/Pages/CreateClientTest.php`
- Modify: `CHANGELOG.md`

---

### Task 1: User-Model Passport-Wiring (dashclip-delivery)

**Files:**
- Modify: `app/Models/User.php`
- Test: `tests/Integration/Models/UserTest.php`

**Interfaces:**
- Consumes: `Laravel\Passport\Contracts\OAuthenticatable`, `Laravel\Passport\HasApiTokens`,
  `N3XT0R\LaravelPassportAuthorizationCore\Models\Concerns\HasPassportScopeGrantsInterface`,
  `N3XT0R\LaravelPassportAuthorizationCore\Models\Traits\HasPassportScopeGrantsTrait` — alle bereits
  über Composer verfügbar (`laravel/passport`, `n3xt0r/laravel-passport-authorization-core`).
- Produces: `User implements OAuthenticatable` (u. a. `tokens()`, `tokenCan()`, `createToken()`) und
  `User implements HasPassportScopeGrantsInterface` (`passportScopeGrants(): MorphMany`) — Task 6
  (Panel-Registrierung/Self-Service) und spätere Sub-Projekte verlassen sich darauf, dass `User` als
  `tokenable` für `PassportScopeGrant` funktioniert.

- [ ] **Step 1: Write the failing test**

Füge in `tests/Integration/Models/UserTest.php` am Ende der Klasse (vor der schließenden `}`) hinzu:

```php
    public function testImplementsOAuthenticatableContract(): void
    {
        $this->assertInstanceOf(
            \Laravel\Passport\Contracts\OAuthenticatable::class,
            new User()
        );
    }

    public function testImplementsHasPassportScopeGrantsInterface(): void
    {
        $this->assertInstanceOf(
            \N3XT0R\LaravelPassportAuthorizationCore\Models\Concerns\HasPassportScopeGrantsInterface::class,
            new User()
        );
    }

    public function testPassportScopeGrantsReturnsMorphManyRelation(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\MorphMany::class,
            $user->passportScopeGrants()
        );
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Models/UserTest.php`
Expected: die drei neuen Tests FAILen (`OAuthenticatable`/`HasPassportScopeGrantsInterface` noch
nicht implementiert, `passportScopeGrants()` existiert nicht).

- [ ] **Step 3: Implement**

In `app/Models/User.php`:

```php
use App\Models\Pivots\ChannelUserPivot;
use App\Models\Pivots\ModelHasRoleTeam;
use App\Repository\RoleRepository;
use App\Repository\TeamRepository;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use N3XT0R\LaravelPassportAuthorizationCore\Models\Concerns\HasPassportScopeGrantsInterface;
use N3XT0R\LaravelPassportAuthorizationCore\Models\Traits\HasPassportScopeGrantsTrait;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery,
                                              HasEmailAuthentication, MustVerifyEmail, HasTenants, HasDefaultTenant,
                                              HasLocalePreference, OAuthenticatable, HasPassportScopeGrantsInterface
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;
    use LogsActivity;
    use HasApiTokens;
    use HasPassportScopeGrantsTrait;
```

(Nur der `use`-Import-Block und die Klassendeklaration ändern sich — der restliche Klassenkörper
bleibt unverändert.)

- [ ] **Step 4: Run test to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Models/UserTest.php`
Expected: PASS (alle Tests der Datei, inkl. der drei neuen).

- [ ] **Step 5: Commit**

```bash
git add app/Models/User.php tests/Integration/Models/UserTest.php
git commit -m "feat(passport): wire OAuthenticatable and scope-grant support onto User

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 2: `api`-Guard, Token-Lifetimes und Sanity-Check-Endpoint (dashclip-delivery)

**Files:**
- Modify: `config/auth.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `routes/api.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/Http/Api/AuthenticatedUserEndpointTest.php`

**Interfaces:**
- Consumes: `User implements OAuthenticatable` (aus Task 1), `Laravel\Passport\Passport` Facade,
  `Laravel\Passport\Passport::actingAs()` Test-Helper.
- Produces: `GET /api/user` (Routenname keiner, Pfad `/api/user`), geschützt durch
  `auth:api`-Middleware — spätere Sub-Projekte (Videos/Channels/Offers/Teams-Endpoints) hängen ihre
  Routen in dieselbe `routes/api.php` ein.

- [ ] **Step 1: Write the failing test**

Erstelle `tests/Feature/Http/Api/AuthenticatedUserEndpointTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api;

use App\Models\User;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class AuthenticatedUserEndpointTest extends DatabaseTestCase
{
    public function testValidBearerTokenReturnsAuthenticatedUser(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $user->getKey()]);
    }

    public function testMissingBearerTokenReturnsUnauthorized(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
    }

    public function testInvalidBearerTokenReturnsUnauthorized(): void
    {
        $response = $this->getJson('/api/user', [
            'Authorization' => 'Bearer this-token-does-not-exist',
        ]);

        $response->assertUnauthorized();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/AuthenticatedUserEndpointTest.php`
Expected: FAIL — Route `/api/user` existiert nicht (404), `api`-Guard nutzt noch keinen
`passport`-Treiber.

- [ ] **Step 3: Implement `config/auth.php`**

Im `'guards'`-Array ergänzen:

```php
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'standard' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
        ],
    ],
```

- [ ] **Step 4: Implement `routes/api.php`**

```php
<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
```

- [ ] **Step 5: Register API routing in `bootstrap/app.php`**

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
```

(Nur die `withRouting()`-Argumente ändern sich — `api:` wird zwischen `web:` und `commands:`
eingefügt.)

- [ ] **Step 6: Configure Passport token lifetimes in `AppServiceProvider::boot()`**

`use Laravel\Passport\Passport;` zum Import-Block hinzufügen, dann in `boot()` (nach dem
bestehenden `Event::listen(Login::class, RecordUserLastLoginListener::class);`-Block, vor
`Resource::scopeToTenant(false);`) ergänzen:

```php
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
```

- [ ] **Step 7: Run test to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Api/AuthenticatedUserEndpointTest.php`
Expected: PASS (alle drei Tests).

- [ ] **Step 8: Commit**

```bash
git add config/auth.php routes/api.php bootstrap/app.php app/Providers/AppServiceProvider.php tests/Feature/Http/Api/AuthenticatedUserEndpointTest.php
git commit -m "feat(api): wire passport api guard and add auth sanity-check endpoint

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 3: Grant-Type-Einschränkung publizieren (dashclip-delivery)

**Files:**
- Create: `config/passport-authorization-core.php` (publiziert)
- Test: `tests/Integration/Services/AllowedGrantTypesTest.php`

**Interfaces:**
- Consumes: `N3XT0R\LaravelPassportAuthorizationCore\Application\UseCases\Grant\GetAllowedGrantTypeOptions`
  (bereits vorhanden im Paket, liest `config('passport-authorization-core.oauth.allowed_grant_types')`).
- Produces: Config-Override, den Task 5/6 (Client-Erstellungsformular) implizit nutzen — keine neue
  PHP-Schnittstelle.

- [ ] **Step 1: Write the failing test**

Erstelle `tests/Integration/Services/AllowedGrantTypesTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use N3XT0R\LaravelPassportAuthorizationCore\Application\UseCases\Grant\GetAllowedGrantTypeOptions;
use Tests\DatabaseTestCase;

final class AllowedGrantTypesTest extends DatabaseTestCase
{
    public function testLegacyGrantTypesAreExcluded(): void
    {
        $keys = app(GetAllowedGrantTypeOptions::class)->execute()->keys();

        $this->assertNotContains('password', $keys);
        $this->assertNotContains('implicit', $keys);
    }

    public function testExpectedGrantTypesAreAllowed(): void
    {
        $keys = app(GetAllowedGrantTypeOptions::class)->execute()->keys();

        $this->assertEqualsCanonicalizing(
            ['authorization_code', 'client_credentials', 'personal_access', 'device'],
            $keys->all()
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Services/AllowedGrantTypesTest.php`
Expected: FAIL — Paket-Default enthält zusätzlich `password` und `implicit`.

- [ ] **Step 3: Publish and override the config**

```bash
docker exec dashclip-delivery-sharing-1 php artisan vendor:publish --tag=passport-authorization-core-config
```

Danach in der publizierten `config/passport-authorization-core.php` den `oauth`-Block anpassen:

```php
    'oauth' => [
        'allowed_grant_types' => [
            'authorization_code',
            'client_credentials',
            'personal_access',
            'device',
        ],
    ],
```

(Alle übrigen Keys der publizierten Datei — `owner_model`, `owner_label_attribute`,
`use_database_scopes`, `cache`, `models` — unverändert lassen, sie entsprechen bereits den
Paket-Defaults, die für dieses Projekt passen.)

- [ ] **Step 4: Run test to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Services/AllowedGrantTypesTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add config/passport-authorization-core.php tests/Integration/Services/AllowedGrantTypesTest.php
git commit -m "chore(passport): restrict allowed OAuth grant types, excluding legacy password/implicit

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 4: `enable_scopes_management`-Namespace-Bugfix (`filament-passport-ui`)

**Repo:** `~/PhpstormProjects/filament-passport-ui`

**Vorbereitung (einmalig vor Task 4):**

```bash
cd ~/PhpstormProjects/filament-passport-ui
git fetch origin main
git checkout main
git pull --ff-only
git checkout -b feature/self-service-mode main
```

Alle Tasks 4 und 5 laufen auf diesem einen Branch.

**Files:**
- Modify: `src/FilamentPassportUiPlugin.php`
- Modify: `config/passport-ui.php`
- Test: `tests/Feature/FilamentPassportUiPluginTest.php`

**Interfaces:**
- Consumes: `Filament\Panel` (`Panel::make()`, `getResources(): array`).
- Produces: `config('passport-ui.enable_scopes_management')` als tatsächlich wirksamer Config-Key —
  Task 5 verlässt sich darauf, dass dieser Key funktioniert (self-service Panels wollen ihn evtl.
  weiterhin auf `true` lassen, aber der Key muss grundsätzlich lesbar sein).

- [ ] **Step 1: Write the failing test**

Erstelle `tests/Feature/FilamentPassportUiPluginTest.php`:

```php
<?php

declare(strict_types=1);

namespace N3XT0R\FilamentPassportUi\Tests\Feature;

use Filament\Panel;
use N3XT0R\FilamentPassportUi\FilamentPassportUiPlugin;
use N3XT0R\FilamentPassportUi\Resources\ClientResource;
use N3XT0R\FilamentPassportUi\Resources\PassportScopeActionsResource;
use N3XT0R\FilamentPassportUi\Resources\PassportScopeResourceResource;
use N3XT0R\FilamentPassportUi\Tests\DatabaseTestCase;

final class FilamentPassportUiPluginTest extends DatabaseTestCase
{
    public function testScopeManagementResourcesAreExcludedWhenDisabledViaConfig(): void
    {
        config(['passport-ui.enable_scopes_management' => false]);

        $panel = Panel::make()->id('test-scopes-disabled');
        FilamentPassportUiPlugin::make()->register($panel);

        $this->assertNotContains(PassportScopeResourceResource::class, $panel->getResources());
        $this->assertNotContains(PassportScopeActionsResource::class, $panel->getResources());
        $this->assertContains(ClientResource::class, $panel->getResources());
    }

    public function testScopeManagementResourcesAreIncludedByDefault(): void
    {
        $panel = Panel::make()->id('test-scopes-enabled');
        FilamentPassportUiPlugin::make()->register($panel);

        $this->assertContains(PassportScopeResourceResource::class, $panel->getResources());
        $this->assertContains(PassportScopeActionsResource::class, $panel->getResources());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
docker run --rm -v ~/PhpstormProjects/filament-passport-ui:/app -w /app composer:2 sh -c "vendor/bin/phpunit --no-coverage --filter=FilamentPassportUiPluginTest"
```
Expected: `testScopeManagementResourcesAreExcludedWhenDisabledViaConfig` FAILt — `config('filament-passport-ui.enable_scopes_management', true)` ist unter dem falschen Namespace, greift also nie, Resource wird trotzdem registriert.

- [ ] **Step 3: Implement**

In `src/FilamentPassportUiPlugin.php`, Methode `registerResources()`:

```php
    protected function registerResources(Panel $panel): void
    {
        $resources = [
            Resources\ClientResource::class,
            Resources\TokenResource::class,
        ];

        if (config('passport-ui.enable_scopes_management', true)) {
            $resources[] = Resources\PassportScopeResourceResource::class;
            $resources[] = Resources\PassportScopeActionsResource::class;
        }


        $panel->resources($resources);
    }
```

(Einzige Änderung: `filament-passport-ui.enable_scopes_management` → `passport-ui.enable_scopes_management`.)

In `config/passport-ui.php` den Key ergänzen:

```php
<?php

use Filament\Support\Icons\Heroicon;

return [

    /*
    |--------------------------------------------------------------------------
    | Navigation Groups
    |--------------------------------------------------------------------------
    |
    | This values controls the navigation group name used by Filament
    | for all Passport-related resources.
    |
    */

    'navigation' => [
        'client_resource' => [
            'group' => 'filament-passport-ui::passport-ui.navigation.group',
            'icon' => Heroicon::OutlinedKey,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scope Management
    |--------------------------------------------------------------------------
    |
    | Whether the PassportScopeResource/PassportScopeAction management
    | resources are registered alongside ClientResource/TokenResource.
    |
    */

    'enable_scopes_management' => true,
];
```

- [ ] **Step 4: Run test to verify it passes**

Run:
```bash
docker run --rm -v ~/PhpstormProjects/filament-passport-ui:/app -w /app composer:2 sh -c "vendor/bin/phpunit --no-coverage --filter=FilamentPassportUiPluginTest"
```
Expected: PASS (beide Tests).

- [ ] **Step 5: Update CHANGELOG**

In `~/PhpstormProjects/filament-passport-ui/CHANGELOG.md`, unter `## [Unreleased]`:

```markdown
## [Unreleased]

### Fixed

- `FilamentPassportUiPlugin` read `config('filament-passport-ui.enable_scopes_management')`, but the package publishes its config under the `passport-ui` namespace (`hasConfigFile('passport-ui')`). The option was therefore never actually configurable — it always fell back to its `true` default regardless of what was published. Fixed to read `config('passport-ui.enable_scopes_management')`, and the key is now defined (default `true`) in the package's own `config/passport-ui.php`.
```

- [ ] **Step 6: Commit**

```bash
cd ~/PhpstormProjects/filament-passport-ui
git add src/FilamentPassportUiPlugin.php config/passport-ui.php tests/Feature/FilamentPassportUiPluginTest.php CHANGELOG.md
git commit -m "fix(config): read enable_scopes_management from the correct passport-ui namespace

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 5: Self-Service-Modus für `ClientResource`/`TokenResource` (`filament-passport-ui`)

**Repo:** `~/PhpstormProjects/filament-passport-ui`, gleicher Branch wie Task 4
(`feature/self-service-mode`).

**Files:**
- Modify: `src/FilamentPassportUiPlugin.php`
- Modify: `src/Resources/ClientResource.php`
- Modify: `src/Resources/TokenResource.php`
- Modify: `src/Resources/ClientResource/Schemas/ClientWizardForm.php`
- Modify: `src/Resources/ClientResource/Pages/CreateClient.php`
- Test: `tests/Feature/Resources/ClientResourceTest.php`, `tests/Feature/Resources/TokenResourceTest.php`, `tests/Feature/Resources/ClientResource/Pages/CreateClientTest.php`

**Interfaces:**
- Consumes: `Filament\Facades\Filament::auth()`, `Filament\Facades\Filament::setCurrentPanel()`,
  `App\Models\User` (Workbench-Test-Model), `N3XT0R\FilamentPassportUi\Database\Factories\ClientFactory`,
  `N3XT0R\FilamentPassportUi\Database\Factories\TokenFactory`.
- Produces: `FilamentPassportUiPlugin::selfService(bool $condition = true): static` und
  `FilamentPassportUiPlugin::isSelfService(): bool` — Task 6 (dashclip-delivery, Panel-Registrierung)
  ruft `FilamentPassportUiPlugin::make()->selfService()` im Standard-Panel auf.

- [ ] **Step 1: Write the failing tests**

Erstelle `tests/Feature/Resources/ClientResourceTest.php` (falls schon vorhanden, Klasse um die
folgende Methode ergänzen, sonst neu anlegen mit der bereits bestehenden
`testNavigationBadgeReturnsClientCount`-Methode plus dieser neuen):

```php
    public function testGetEloquentQueryScopesToCurrentUserWhenSelfServiceEnabled(): void
    {
        config()->set('passport-authorization-core.use_database_scopes', false);

        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();

        $ownClient = ClientFactory::new()->create([
            'owner_id' => $owner->getKey(),
            'owner_type' => $owner->getMorphClass(),
        ]);
        ClientFactory::new()->create([
            'owner_id' => $otherOwner->getKey(),
            'owner_type' => $otherOwner->getMorphClass(),
        ]);

        $panel = \Filament\Panel::make()->id('client-self-service-test');
        FilamentPassportUiPlugin::make()->selfService()->register($panel);
        \Filament\Facades\Filament::setCurrentPanel($panel);

        $this->actingAs($owner, 'web');

        $clients = ClientResource::getEloquentQuery()->get();

        $this->assertCount(1, $clients);
        $this->assertTrue($clients->first()->is($ownClient));
    }

    public function testGetEloquentQueryReturnsAllClientsWhenSelfServiceDisabled(): void
    {
        config()->set('passport-authorization-core.use_database_scopes', false);

        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();

        ClientFactory::new()->create(['owner_id' => $owner->getKey(), 'owner_type' => $owner->getMorphClass()]);
        ClientFactory::new()->create(['owner_id' => $otherOwner->getKey(), 'owner_type' => $otherOwner->getMorphClass()]);

        $panel = \Filament\Panel::make()->id('client-admin-mode-test');
        FilamentPassportUiPlugin::make()->register($panel);
        \Filament\Facades\Filament::setCurrentPanel($panel);

        $this->actingAs($owner, 'web');

        $this->assertCount(2, ClientResource::getEloquentQuery()->get());
    }
```

Ergänze die nötigen `use`-Imports am Dateikopf: `use N3XT0R\FilamentPassportUi\FilamentPassportUiPlugin;`
(zusätzlich zu den bereits vorhandenen `App\Models\User`, `Livewire\Livewire`, `ClientFactory`,
`ClientResource`, `ListClients`, `DatabaseTestCase`).

Erstelle `tests/Feature/Resources/TokenResourceTest.php` (neu, falls nicht vorhanden — Feature-Test
auf Resource-Ebene analog zum bestehenden Muster bei `ClientResourceTest`):

```php
<?php

declare(strict_types=1);

namespace N3XT0R\FilamentPassportUi\Tests\Feature\Resources;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Panel;
use N3XT0R\FilamentPassportUi\Database\Factories\TokenFactory;
use N3XT0R\FilamentPassportUi\FilamentPassportUiPlugin;
use N3XT0R\FilamentPassportUi\Resources\TokenResource;
use N3XT0R\FilamentPassportUi\Tests\DatabaseTestCase;

final class TokenResourceTest extends DatabaseTestCase
{
    public function testGetEloquentQueryScopesToCurrentUserWhenSelfServiceEnabled(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();

        $ownToken = TokenFactory::new()->withUserId($owner->getKey())->create();
        TokenFactory::new()->withUserId($otherOwner->getKey())->create();

        $panel = Panel::make()->id('token-self-service-test');
        FilamentPassportUiPlugin::make()->selfService()->register($panel);
        Filament::setCurrentPanel($panel);

        $this->actingAs($owner, 'web');

        $tokens = TokenResource::getEloquentQuery()->get();

        $this->assertCount(1, $tokens);
        $this->assertSame($ownToken->getKey(), $tokens->first()->getKey());
    }

    public function testGetEloquentQueryReturnsAllTokensWhenSelfServiceDisabled(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();

        TokenFactory::new()->withUserId($owner->getKey())->create();
        TokenFactory::new()->withUserId($otherOwner->getKey())->create();

        $panel = Panel::make()->id('token-admin-mode-test');
        FilamentPassportUiPlugin::make()->register($panel);
        Filament::setCurrentPanel($panel);

        $this->actingAs($owner, 'web');

        $this->assertCount(2, TokenResource::getEloquentQuery()->get());
    }
}
```

Erstelle `tests/Feature/Resources/ClientResource/Pages/CreateClientTest.php` (falls schon
vorhanden, Klasse um die folgende Methode ergänzen):

```php
    public function testSelfServiceModeForcesOwnerToCurrentUserRegardlessOfSubmittedData(): void
    {
        config()->set('passport-authorization-core.use_database_scopes', false);

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $panel = \Filament\Panel::make()->id('create-client-self-service-test');
        FilamentPassportUiPlugin::make()->selfService()->register($panel);
        \Filament\Facades\Filament::setCurrentPanel($panel);

        $this->actingAs($owner, 'web');

        Livewire::test(CreateClient::class)
            ->fillForm([
                'name' => 'My Self-Service Client',
                'grant_type' => 'personal_access',
                'owner' => $otherUser->getKey(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $client = \N3XT0R\LaravelPassportAuthorizationCore\Models\Passport\Client::where('name', 'My Self-Service Client')->firstOrFail();

        $this->assertSame($owner->getKey(), $client->owner_id);
    }
```

Ergänze am Dateikopf `use N3XT0R\FilamentPassportUi\FilamentPassportUiPlugin;`, falls noch nicht
importiert.

- [ ] **Step 2: Run tests to verify they fail**

Run:
```bash
docker run --rm -v ~/PhpstormProjects/filament-passport-ui:/app -w /app composer:2 sh -c "vendor/bin/phpunit --no-coverage --filter='ClientResourceTest|TokenResourceTest|CreateClientTest'"
```
Expected: FAIL — `FilamentPassportUiPlugin::selfService()` existiert nicht (Fatal Error / Method
not found).

- [ ] **Step 3: Add `selfService()`/`isSelfService()` to the plugin**

In `src/FilamentPassportUiPlugin.php`:

```php
<?php

namespace N3XT0R\FilamentPassportUi;

use Filament\Contracts\Plugin as FilamentPlugin;
use Filament\Panel;

class FilamentPassportUiPlugin implements FilamentPlugin
{
    protected bool $selfService = false;

    public function getId(): string
    {
        return 'filament-passport-ui';
    }

    /**
     * Restrict ClientResource/TokenResource to records owned by the
     * currently authenticated user, instead of the full admin-style
     * management view.
     */
    public function selfService(bool $condition = true): static
    {
        $this->selfService = $condition;

        return $this;
    }

    public function isSelfService(): bool
    {
        return $this->selfService;
    }

    public function register(Panel $panel): void
    {
        $this->registerResources($panel);
    }

    protected function registerResources(Panel $panel): void
    {
        $resources = [
            Resources\ClientResource::class,
            Resources\TokenResource::class,
        ];

        if (config('passport-ui.enable_scopes_management', true)) {
            $resources[] = Resources\PassportScopeResourceResource::class;
            $resources[] = Resources\PassportScopeActionsResource::class;
        }


        $panel->resources($resources);
    }

    /**
     * Bootstrap any plugin panel services if any exists
     * @param Panel $panel
     * @return void
     */
    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Create a new plugin instance from the container.
     * @note This method assumes the plugin is registered with Filament
     * and is possible to override/extend the plugin class via DI.
     * @return static
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Get the plugin instance from the Filament container.
     * @return static
     */
    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(static::make()->getId());

        return $plugin;
    }
}
```

(Änderungen ggü. dem Task-4-Stand: neues `protected bool $selfService = false;`-Property, neue
`selfService()`/`isSelfService()`-Methoden. Alles andere unverändert.)

- [ ] **Step 4: Scope `ClientResource::getEloquentQuery()`**

In `src/Resources/ClientResource.php`, `use`-Block ergänzen um
`use Filament\Facades\Filament;` und `use Illuminate\Database\Eloquent\Builder;`, dann Methode
ergänzen (z. B. direkt vor `getModel()`):

```php
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (static::get()->isSelfService()) {
            $user = Filament::auth()->user();

            $query
                ->where('owner_id', $user?->getKey())
                ->where('owner_type', $user?->getMorphClass());
        }

        return $query;
    }
```

(`static::get()` löst über `BaseManagementResource extends Resource` nicht automatisch die Plugin-
Instanz auf — nutze stattdessen den vollen Klassennamen: `FilamentPassportUiPlugin::get()`. Import
`use N3XT0R\FilamentPassportUi\FilamentPassportUiPlugin;` ergänzen und in der Methode
`FilamentPassportUiPlugin::get()->isSelfService()` verwenden statt `static::get()`.)

- [ ] **Step 5: Scope `TokenResource::getEloquentQuery()`**

In `src/Resources/TokenResource.php`, `use`-Block ergänzen um `use Filament\Facades\Filament;`,
`use Illuminate\Database\Eloquent\Builder;`, `use N3XT0R\FilamentPassportUi\FilamentPassportUiPlugin;`,
dann Methode ergänzen (vor `getModel()`):

```php
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (FilamentPassportUiPlugin::get()->isSelfService()) {
            $query->where('user_id', Filament::auth()->id());
        }

        return $query;
    }
```

- [ ] **Step 6: Lock `OwnerSelect` in the create-form UI**

In `src/Resources/ClientResource/Schemas/ClientWizardForm.php`, `use`-Block ergänzen um
`use Filament\Facades\Filament;` und `use N3XT0R\FilamentPassportUi\FilamentPassportUiPlugin;`.
In `getClientComponents()` die bestehende `OwnerSelect::make()`-Zeile erweitern:

```php
            OwnerSelect::make()
                ->disabled(fn(): bool => FilamentPassportUiPlugin::get()->isSelfService())
                ->default(
                    fn(): int|string|null => FilamentPassportUiPlugin::get()->isSelfService()
                        ? Filament::auth()->id()
                        : null
                )
                ->required(function (Get $get): bool {
                    $grantType = $get('grant_type');

                    if ($grantType === null) {
                        return false;
                    }

                    return app(NeedsUserPermissionState::class)
                        ->execute($grantType);
                }),
```

(Nur die neuen `->disabled()`- und `->default()`-Aufrufe werden vor dem bestehenden
`->required(...)` eingefügt — der Rest der Methode bleibt unverändert. `disabled()` ist reine UX;
die eigentliche Durchsetzung passiert serverseitig in Step 7.)

- [ ] **Step 7: Enforce the owner server-side in `CreateClient::handleRecordCreation()`**

In `src/Resources/ClientResource/Pages/CreateClient.php`, `use`-Block ergänzen um
`use Filament\Facades\Filament;` und `use N3XT0R\FilamentPassportUi\FilamentPassportUiPlugin;`.
Am Anfang von `handleRecordCreation(array $data): Model` (vor der bestehenden
`$actor = Filament::auth()->user();`-Zeile — die bleibt, `$actor` wird weiterverwendet) ergänzen:

```php
    protected function handleRecordCreation(array $data): Model
    {
        $actor = Filament::auth()->user();

        if (FilamentPassportUiPlugin::get()->isSelfService()) {
            $data['owner'] = $actor?->getKey();
        }

        $userScopes = [];
        // ... Rest der Methode unverändert
```

- [ ] **Step 8: Run tests to verify they pass**

Run:
```bash
docker run --rm -v ~/PhpstormProjects/filament-passport-ui:/app -w /app composer:2 sh -c "vendor/bin/phpunit --no-coverage --filter='ClientResourceTest|TokenResourceTest|CreateClientTest'"
```
Expected: PASS (alle Tests, inkl. der bisherigen `testNavigationBadgeReturnsClientCount`).

- [ ] **Step 9: Run the full package suite (regression check)**

Run:
```bash
docker run --rm -v ~/PhpstormProjects/filament-passport-ui:/app -w /app composer:2 sh -c "vendor/bin/phpunit --no-coverage"
```
Expected: OK, keine Regressionen im bestehenden Admin-Pfad.

- [ ] **Step 10: Update CHANGELOG**

In `~/PhpstormProjects/filament-passport-ui/CHANGELOG.md`, unter `## [Unreleased]` (gleicher
Abschnitt wie Task 4, `### Added` ergänzen):

```markdown
### Added

- New self-service mode: `FilamentPassportUiPlugin::make()->selfService()` restricts `ClientResource`/`TokenResource` to records owned by the currently authenticated user, and locks the client-creation form's owner field to that user server-side. Intended for panels where non-admin users manage their own OAuth clients/tokens without seeing other users' data.
```

- [ ] **Step 11: Commit**

```bash
cd ~/PhpstormProjects/filament-passport-ui
git add src/FilamentPassportUiPlugin.php src/Resources/ClientResource.php src/Resources/TokenResource.php src/Resources/ClientResource/Schemas/ClientWizardForm.php src/Resources/ClientResource/Pages/CreateClient.php tests/Feature/Resources/ClientResourceTest.php tests/Feature/Resources/TokenResourceTest.php tests/Feature/Resources/ClientResource/Pages/CreateClientTest.php CHANGELOG.md
git commit -m "feat(resources): add self-service mode scoping Client/Token resources to the owner

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

**Handoff nach Task 5:** Beide Commits (Task 4 + 5) liegen lokal auf
`feature/self-service-mode`. Push, Merge, Tag/Release macht der User manuell (etabliertes Muster).
Task 6 kann erst starten, sobald eine neue Version tatsächlich released ist.

---

### Task 6: Composer-Bump + Panel-Registrierung (dashclip-delivery)

**Files:**
- Modify: `composer.json` / `composer.lock`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `app/Providers/Filament/PanelUserPanelProvider.php`
- Test: `tests/Feature/Filament/Standard/Resources/PassportSelfServiceTest.php`
- Modify: `CHANGELOG.md`

**Interfaces:**
- Consumes: `N3XT0R\FilamentPassportUi\FilamentPassportUiPlugin::make()->selfService()` (aus Task 5,
  muss als released Composer-Version verfügbar sein).
- Produces: Abschluss von Sub-Projekt 1 — Sub-Projekt 2 (Core API Resources) kann darauf aufbauen,
  dass `routes/api.php`, der `api`-Guard und die Self-Service-UI existieren.

- [ ] **Step 1: Verify the package release is available**

Frage den User, ob die neue `filament-passport-ui`-Version (mit den Änderungen aus Task 4+5) bereits
gepusht und getaggt ist. Falls nein: hier stoppen und warten, bevor fortgefahren wird.

- [ ] **Step 2: Bump the dependency**

Run: `docker exec dashclip-delivery-sharing-1 composer update n3xt0r/filament-passport-ui --with-all-dependencies`

Prüfe `composer.lock`, dass `n3xt0r/filament-passport-ui` (und transitiv
`n3xt0r/laravel-passport-authorization-core`, falls mitversioniert) auf die neue Version zeigt.

- [ ] **Step 3: Write the failing test**

Erstelle `tests/Feature/Filament/Standard/Resources/PassportSelfServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Resources;

use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use N3XT0R\FilamentPassportUi\Database\Factories\ClientFactory;
use N3XT0R\FilamentPassportUi\Resources\ClientResource\Pages\ListClients;
use Tests\DatabaseTestCase;

final class PassportSelfServiceTest extends DatabaseTestCase
{
    public function testStandardUserOnlySeesOwnClients(): void
    {
        $user = User::factory()->standard()->create();
        $otherUser = User::factory()->standard()->create();

        $ownClient = ClientFactory::new()->create([
            'owner_id' => $user->getKey(),
            'owner_type' => $user->getMorphClass(),
        ]);
        ClientFactory::new()->create([
            'owner_id' => $otherUser->getKey(),
            'owner_type' => $otherUser->getMorphClass(),
        ]);

        $this->actingAs($user, 'standard');
        Filament::setCurrentPanel(Filament::getPanel('standard'));

        Livewire::test(ListClients::class)
            ->assertCanSeeTableRecords([$ownClient])
            ->assertCanNotSeeTableRecords(
                \N3XT0R\LaravelPassportAuthorizationCore\Models\Passport\Client::query()
                    ->whereKeyNot($ownClient->getKey())
                    ->get()
            );
    }
}
```

- [ ] **Step 4: Run test to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Filament/Standard/Resources/PassportSelfServiceTest.php`
Expected: FAIL — Plugin ist in keinem Panel registriert, `ListClients`-Route/Klasse nicht im
Standard-Panel discoverbar (Filament wirft beim `Livewire::test()`-Aufruf einen Fehler bzw. die
Query liefert nichts, weil das Panel den Resource-Kontext nicht kennt).

- [ ] **Step 5: Register the plugin in both panels**

In `app/Providers/Filament/AdminPanelProvider.php`: `use N3XT0R\FilamentPassportUi\FilamentPassportUiPlugin;`
zum Import-Block hinzufügen, dann in `addPlugins()` in das bestehende `$panel->plugins([...])`-Array
ergänzen (vor `LaravelWebdavServerFilamentPlugin::make(),`):

```php
    protected function addPlugins(Panel $panel): Panel
    {
        return $panel->plugins([
            FilamentShieldPlugin::make()
                ->centralApp()
                ->scopeToTenant(false)
                ->tenantRelationshipName('teams')
                ->tenantOwnershipRelationshipName('owner'),
            FilamentLogViewerPlugin::make()
                ->navigationGroup('System')
                ->navigationLabel('Log Viewer'),
            FilamentPassportUiPlugin::make(),
            LaravelWebdavServerFilamentPlugin::make(),
        ]);
    }
```

In `app/Providers/Filament/PanelUserPanelProvider.php`: gleicher Import, dann in `addPlugins()`
ergänzen (vor `LaravelWebdavServerFilamentPlugin::make()...`):

```php
    protected function addPlugins(Panel $panel): Panel
    {
        return $panel->plugins([
            FilamentShieldPlugin::make()
                ->registerNavigation(false)
                ->centralApp(false)
                ->localizePermissionLabels()
                ->scopeToTenant(false),
            FilamentPassportUiPlugin::make()->selfService(),
            LaravelWebdavServerFilamentPlugin::make()
                ->withoutAdminAccountResource()
                ->withUserAccountResource(),
        ]);
    }
```

- [ ] **Step 6: Run test to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Filament/Standard/Resources/PassportSelfServiceTest.php`
Expected: PASS.

- [ ] **Step 7: Run the full suite (regression check)**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel`
Expected: OK, keine Regressionen (insbesondere Admin-Panel-Tests, da dort jetzt ebenfalls neue
Ressourcen registriert sind).

- [ ] **Step 8: Update CHANGELOG**

In `CHANGELOG.md`, unter `## [Unreleased]` → `### Added`:

```markdown
- **REST API OAuth2 foundation** - Passport is wired up end-to-end (bearer-token authentication via a new `api` guard, verified through a `GET /api/user` sanity-check endpoint). Standard-panel users can now self-manage their own OAuth clients and personal access tokens with their assigned scopes; the admin panel retains full client/token management. This is the foundation sub-project for the broader REST API (Ticket #250) — actual domain endpoints follow in a later sub-project.
```

- [ ] **Step 9: Commit**

```bash
git add composer.json composer.lock app/Providers/Filament/AdminPanelProvider.php app/Providers/Filament/PanelUserPanelProvider.php tests/Feature/Filament/Standard/Resources/PassportSelfServiceTest.php CHANGELOG.md
git commit -m "feat(passport): register self-service OAuth client/token management in both panels

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Plan Self-Review Notes

- **Spec coverage:** A) User-Model/Guard/Grant-Types/Endpoint → Tasks 1–3. B) Self-Service-Modus +
  `enable_scopes_management`-Bugfix → Tasks 4–5. C) Panel-Registrierung → Task 6. D)
  Fehlerbehandlung ist durch Passport-Standardverhalten abgedeckt (kein eigener Task nötig, in Task
  2 getestet). Tests-Abschnitt der Spec ist 1:1 auf die Tasks gemappt.
- **Placeholder-Scan:** keine TBD/TODO; alle Codeblöcke sind vollständig, keine
  "ähnlich wie oben"-Verweise.
- **Type-Konsistenz geprüft:** `FilamentPassportUiPlugin::selfService()`/`isSelfService()` (Task 5)
  wird in Task 6 exakt mit diesen Namen aufgerufen; `ClientResource::getEloquentQuery()`/
  `TokenResource::getEloquentQuery()` (Task 5) matchen die in Task 6 indirekt über
  `Livewire::test(ListClients::class)` ausgelöste Query.
