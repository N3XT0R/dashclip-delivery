# User Locale Preference Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let users pick a preferred language in their profile; apply it to both the Filament UI (Admin + Standard panels) and outgoing mail/notifications.

**Architecture:** A nullable `locale` column on `users` backs `User::preferredLocale()` (already wired via the pre-existing `HasLocalePreference` interface, currently a stub). A new `LocaleDiscoveryService` scans `lang/` for available app locales. A new `SetUserLocale` middleware, registered in both panel providers, calls `App::setLocale()` per request for authenticated users — this is what makes the UI itself localized, and it's the same mechanism Laravel's notification/mail system already consults via `HasLocalePreference`. A new Select field on the existing shared `EditProfile` page lets the user choose.

**Tech Stack:** Laravel 11+ (`casts()` method style), Filament v4 (Admin + Standard panels), PHPUnit (`DatabaseTestCase`, `Tests\TestCase`), Docker test runner with `--parallel`.

## Global Constraints

- `users` table gets one new nullable `locale` (varchar(10)) column. It is added to `User::$fillable` (system + user editable via the profile form) — no cast needed (plain string).
- `User::preferredLocale(): string` returns `$this->locale ?? config('app.locale')`.
- `LocaleDiscoveryService::list()` returns app-owned locale codes: top-level directories in `lang/`, excluding `vendor`. No caching decorator (YAGNI — scanning 2-3 directories is trivially cheap, unlike the reflection-heavy `NotificationDiscoveryService` this is modeled after).
- `LocaleDiscoveryService::label(string $locale): string` returns a human-readable name from a small hardcoded map (`de` → `Deutsch`, `en` → `English`); unknown codes fall back to `strtoupper($locale)`.
- `App\Http\Middleware\SetUserLocale` — no `*Middleware` suffix convention exists in this codebase (ADR 0002 has no row for Middleware); named per Laravel's own core-middleware style (`EncryptCookies`, `TrimStrings`). Registered in **both** `AdminPanelProvider::addMiddlewares()` and `PanelUserPanelProvider::addMiddlewares()`, immediately after `AuthenticateSession::class` (must run after the session/auth guard has resolved `$request->user()`).
- Guests (no authenticated user) are left untouched by the middleware — no branch, no default override, `App::getLocale()` keeps whatever `config('app.locale')` set.
- The profile Select field (`locale`) is nullable, with a "system default" placeholder — leaving it empty stores `null`, which is exactly what `preferredLocale()`'s fallback expects.
- `EditProfile` is the **shared** profile page for both panels (`->profile(EditProfile::class)` in both providers) — one implementation covers both.
- Test layering (ADR 0001, verified against existing sibling tests in this codebase):
  - `LocaleDiscoveryService` → `tests/Integration/Services/`, extends `Tests\TestCase` (no DB needed), mirroring `NotificationDiscoveryServiceTest`.
  - `User::preferredLocale()` / fillable → `tests/Integration/Models/UserTest.php` (existing file, add to it).
  - Middleware → `tests/Feature/Http/Middleware/` — a real HTTP request must flow through the actual panel middleware stack to prove the registration point is correct, not just the class logic in isolation.
  - `EditProfile` field → `tests/Integration/Filament/Admin/Pages/Auth/` (existing directory, new file), mirroring `EditProfilePageTest`/`EditProfileNotificationsTest`.
- CHANGELOG.md entry under `## [Unreleased]`, in **English** (ADR 0006 — the whole file is English, no exceptions).

Reference spec: `docs/superpowers/specs/2026-08-19-user-locale-preference-design.md`

---

## Task 1: `users` migration + `User::preferredLocale()`

**Files:**
- Create: `database/migrations/2026_08_19_120000_add_locale_to_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `tests/Integration/Models/UserTest.php` (existing file — add to it, don't touch existing tests)

**Interfaces:**
- Produces: `users.locale` (nullable varchar(10)); `User::preferredLocale(): string` returning the stored locale or `config('app.locale')`. Later tasks (middleware, profile form) read/write `$user->locale` and call `preferredLocale()`.

- [ ] **Step 1: Write the failing tests**

Add these three test methods to the existing `UserTest` class in `tests/Integration/Models/UserTest.php` (append after the last existing method, before the closing `}`):

```php
public function testPreferredLocaleReturnsStoredLocale(): void
{
    $user = User::factory()->create(['locale' => 'en']);

    $this->assertSame('en', $user->preferredLocale());
}

public function testPreferredLocaleFallsBackToAppDefaultWhenNull(): void
{
    $user = User::factory()->create(['locale' => null]);

    $this->assertSame(config('app.locale'), $user->preferredLocale());
}

public function testLocaleIsMassAssignable(): void
{
    $user = User::factory()->make();
    $user->fill(['locale' => 'en']);

    $this->assertSame('en', $user->locale);
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Models/UserTest.php`

Expected: FAIL — column `locale` does not exist yet, and `preferredLocale()` still returns the hardcoded `config('app.locale')` regardless of the stored value (so the first test fails at the assertion, not with an error).

- [ ] **Step 3: Create the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->string('locale', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
```

- [ ] **Step 4: Update `User` model**

In `app/Models/User.php`, add `'locale'` to the `$fillable` array (find the existing `protected $fillable = [...]` block near the top of the class and add it, e.g. after `'submitted_name'`):

```php
protected $fillable = [
    'name',
    'submitted_name',
    'locale',
    'email',
    'password',
    'onboarding_completed',
    'terms_accepted_at',
];
```

Replace the existing `preferredLocale()` method (currently near the bottom of the class, right before the closing `}`):

```php
public function preferredLocale(): string
{
    return $this->locale ?? config('app.locale');
}
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Models/UserTest.php`

Expected: PASS (all methods, including the 3 new ones and the pre-existing ones)

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_08_19_120000_add_locale_to_users_table.php app/Models/User.php \
  tests/Integration/Models/UserTest.php
git commit -m "feat(users): add locale column and wire preferredLocale()

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Task 2: `LocaleDiscoveryService`

**Files:**
- Create: `app/Services/LocaleDiscoveryService.php`
- Create: `tests/Integration/Services/LocaleDiscoveryServiceTest.php`

**Interfaces:**
- Produces: `LocaleDiscoveryService::list(): array` (list of locale code strings), `LocaleDiscoveryService::label(string $locale): string`. Task 4's profile form calls both by these exact names.

- [ ] **Step 1: Write the failing test**

Create `tests/Integration/Services/LocaleDiscoveryServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Services\LocaleDiscoveryService;
use Tests\TestCase;

final class LocaleDiscoveryServiceTest extends TestCase
{
    private LocaleDiscoveryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LocaleDiscoveryService();
    }

    public function testListContainsAppLocales(): void
    {
        $result = $this->service->list();

        $this->assertContains('de', $result);
        $this->assertContains('en', $result);
    }

    public function testListExcludesVendorDirectory(): void
    {
        $result = $this->service->list();

        $this->assertNotContains('vendor', $result);
    }

    public function testLabelReturnsKnownDisplayName(): void
    {
        $this->assertSame('Deutsch', $this->service->label('de'));
        $this->assertSame('English', $this->service->label('en'));
    }

    public function testLabelFallsBackToUppercaseCodeForUnknownLocale(): void
    {
        $this->assertSame('FR', $this->service->label('fr'));
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Services/LocaleDiscoveryServiceTest.php`

Expected: FAIL — `App\Services\LocaleDiscoveryService` does not exist yet.

- [ ] **Step 3: Create the service**

```php
<?php

declare(strict_types=1);

namespace App\Services;

class LocaleDiscoveryService
{
    private const LABELS = [
        'de' => 'Deutsch',
        'en' => 'English',
    ];

    /**
     * App-owned available locale codes: top-level directories in lang/, excluding "vendor".
     *
     * @return list<string>
     */
    public function list(): array
    {
        $locales = [];

        foreach (scandir(lang_path()) as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === 'vendor') {
                continue;
            }

            if (is_dir(lang_path($entry))) {
                $locales[] = $entry;
            }
        }

        return $locales;
    }

    /**
     * Human-readable display name for a locale code; unknown codes fall back to the uppercase code.
     */
    public function label(string $locale): string
    {
        return self::LABELS[$locale] ?? strtoupper($locale);
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Services/LocaleDiscoveryServiceTest.php`

Expected: PASS (4/4)

- [ ] **Step 5: Commit**

```bash
git add app/Services/LocaleDiscoveryService.php tests/Integration/Services/LocaleDiscoveryServiceTest.php
git commit -m "feat(locale): add LocaleDiscoveryService for available app locales

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Task 3: `SetUserLocale` middleware, registered in both panels

**Files:**
- Create: `app/Http/Middleware/SetUserLocale.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `app/Providers/Filament/PanelUserPanelProvider.php`
- Create: `tests/Feature/Http/Middleware/SetUserLocaleTest.php`

**Interfaces:**
- Consumes: `User::preferredLocale(): string` (Task 1).
- Produces: `App\Http\Middleware\SetUserLocale::handle()`, registered in both panels' middleware stacks. No later task depends on this class directly — it's a request-lifecycle side effect.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Http/Middleware/SetUserLocaleTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\App;
use Tests\DatabaseTestCase;

final class SetUserLocaleTest extends DatabaseTestCase
{
    public function testAuthenticatedAdminUserLocaleIsAppliedInAdminPanel(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'en']);

        $this->actingAs($user)
            ->get(route('filament.admin.auth.profile'))
            ->assertOk();

        $this->assertSame('en', App::getLocale());
    }

    public function testAuthenticatedStandardUserLocaleIsAppliedInStandardPanel(): void
    {
        $user = User::factory()->withOwnTeam()->standard(GuardEnum::STANDARD)->create(['locale' => 'en']);
        $tenant = app(TeamRepository::class)->getDefaultTeamForUser($user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($tenant, true);

        $this->actingAs($user, GuardEnum::STANDARD->value)
            ->get(route('filament.standard.auth.profile'))
            ->assertOk();

        $this->assertSame('en', App::getLocale());
    }

    public function testGuestRequestLeavesDefaultLocaleUnchanged(): void
    {
        $this->get(route('filament.admin.auth.profile'));

        $this->assertSame(config('app.locale'), App::getLocale());
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Middleware/SetUserLocaleTest.php`

Expected: FAIL — the first two tests fail because nothing sets the locale yet (`App::getLocale()` stays at `config('app.locale')`, e.g. `'de'`, not `'en'`); the guest test passes trivially already (nothing to fix there, it's a baseline/regression guard).

- [ ] **Step 3: Create the middleware**

```php
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
```

- [ ] **Step 4: Register the middleware in both panel providers**

In `app/Providers/Filament/AdminPanelProvider.php`, add the import (alongside the other `use App\...`/`use Illuminate\...` imports, e.g. right after `use App\Filament\Pages\Auth\Login;`):

```php
use App\Http\Middleware\SetUserLocale;
```

Then in `addMiddlewares()`, add the class right after `AuthenticateSession::class,`:

```php
protected function addMiddlewares(Panel $panel): Panel
{
    return $panel->middleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        AuthenticateSession::class,
        SetUserLocale::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
        SubstituteBindings::class,
        DisableBladeIconComponents::class,
        DispatchServingFilamentEvent::class,
    ]);
}
```

Do the exact same two edits (import + `addMiddlewares()` insertion) in `app/Providers/Filament/PanelUserPanelProvider.php`.

- [ ] **Step 5: Run the tests to verify they pass**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Http/Middleware/SetUserLocaleTest.php`

Expected: PASS (3/3)

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware/SetUserLocale.php app/Providers/Filament/AdminPanelProvider.php \
  app/Providers/Filament/PanelUserPanelProvider.php tests/Feature/Http/Middleware/SetUserLocaleTest.php
git commit -m "feat(locale): apply user's preferred locale per request in both panels

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Task 4: Profile language field + translations

**Files:**
- Modify: `app/Filament/Admin/Pages/Auth/EditProfile.php`
- Modify: `lang/de/filament.php`, `lang/en/filament.php`
- Create: `tests/Integration/Filament/Admin/Pages/Auth/EditProfileLocaleTest.php`

**Interfaces:**
- Consumes: `LocaleDiscoveryService::list()` / `::label()` (Task 2).

- [ ] **Step 1: Write the failing test**

Create `tests/Integration/Filament/Admin/Pages/Auth/EditProfileLocaleTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Admin\Pages\Auth;

use App\Filament\Admin\Pages\Auth\EditProfile;
use App\Models\User;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class EditProfileLocaleTest extends DatabaseTestCase
{
    public function testLocaleFieldExists(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->assertFormFieldExists('locale');
    }

    public function testSavingLocalePersistsToUserRecord(): void
    {
        $user = User::factory()->create(['locale' => null]);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm(['locale' => 'en'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function testLeavingLocaleEmptyPersistsNull(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm(['locale' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($user->fresh()->locale);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Filament/Admin/Pages/Auth/EditProfileLocaleTest.php`

Expected: FAIL — no `locale` form field exists yet.

- [ ] **Step 3: Add translation keys**

In `lang/de/filament.php`, inside the `'admin' => ['labels' => [...]]` array, insert these two lines in alphabetical position — right after the `'headers' => 'Header',` line and before `'mail_log' => 'Mail Log',`:

```php
'locale' => 'Sprache',
'locale_system_default' => 'Systemstandard',
```

In `lang/en/filament.php`, same position (after `'headers' => 'Header',`, before `'mail_log' => 'Mail Log',`):

```php
'locale' => 'Language',
'locale_system_default' => 'System default',
```

- [ ] **Step 4: Add the form field to `EditProfile`**

In `app/Filament/Admin/Pages/Auth/EditProfile.php`, add two imports (alongside the existing ones):

```php
use App\Services\LocaleDiscoveryService;
use Filament\Forms\Components\Select;
```

Add a new method (e.g. right after `getSubmittedNameComponent()`):

```php
protected function getLocaleComponent(): Component
{
    $discovery = app(LocaleDiscoveryService::class);
    $options = collect($discovery->list())
        ->mapWithKeys(fn (string $locale) => [$locale => $discovery->label($locale)])
        ->toArray();

    return Select::make('locale')
        ->label(__('filament.admin.labels.locale'))
        ->options($options)
        ->native(false)
        ->placeholder(__('filament.admin.labels.locale_system_default'));
}
```

Add it to the `form()` method's components array, right before `$this->getNotificationComponent(),`:

```php
public function form(Schema $schema): Schema
{
    /**
     * @var TextInput $nameComponent
     */
    $nameComponent = $this->getNameFormComponent();
    return $schema
        ->components([
            $nameComponent
                ->unique(),
            $this->getSubmittedNameComponent(),
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getLocaleComponent(),
            $this->getNotificationComponent(),
        ]);
}
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Filament/Admin/Pages/Auth/EditProfileLocaleTest.php`

Expected: PASS (3/3)

- [ ] **Step 6: Run the pre-existing `EditProfilePageTest` and `EditProfileNotificationsTest` to confirm no regression**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Filament/Admin/Pages/Auth/EditProfilePageTest.php`
Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Filament/Admin/Pages/Auth/EditProfileNotificationsTest.php`

Expected: both PASS (unaffected — the new field is additive).

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Admin/Pages/Auth/EditProfile.php lang/de/filament.php lang/en/filament.php \
  tests/Integration/Filament/Admin/Pages/Auth/EditProfileLocaleTest.php
git commit -m "feat(profile): add language selection field to shared profile page

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Task 5: CHANGELOG entry

**Files:**
- Modify: `CHANGELOG.md`

**Interfaces:** none — documentation only.

- [ ] **Step 1: Add the `[Unreleased]` entry**

In `CHANGELOG.md`, under `## [Unreleased]`, add to the existing `### Added` subsection if one is already present at that point (check the file first — other work may have added one), otherwise create it:

```markdown
### Added
- **User language preference**
    - users can now pick a preferred language in their profile; it is applied to both the Admin
      and Standard panel UI and to outgoing mail/notifications, with a "system default" option
      when left unset.
```

- [ ] **Step 2: Verify the file renders sensibly**

Read the top of `CHANGELOG.md` to confirm the new entry sits correctly under `## [Unreleased]`, in English, matching the surrounding house style.

- [ ] **Step 3: Commit**

```bash
git add CHANGELOG.md
git commit -m "docs(changelog): add user language preference entry

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Self-Review Notes

- **Spec coverage:** migration + `preferredLocale()` (Task 1), locale discovery (Task 2), middleware in both panels (Task 3), profile field + translations (Task 4), changelog (Task 5) — every spec section has a task.
- **Placeholder scan:** no TBD/TODO; every step has runnable code.
- **Type consistency:** `LocaleDiscoveryService::list()`/`::label()` (Task 2) are called with matching names/signatures in Task 4's `getLocaleComponent()`; `User::preferredLocale()` (Task 1) is consumed identically by `SetUserLocale` (Task 3) and implicitly by Laravel's `HasLocalePreference`-aware mail/notification dispatch (already wired from the previous feature branch, no new code needed there).
