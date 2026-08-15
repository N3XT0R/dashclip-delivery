# Last-Login-Tracking & Inaktivitäts-Erinnerungsmail Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Track when each user last logged in, and send users (channel operators / regular Standard-panel users, not pure admin accounts) a repeatable, opt-outable reminder email when they have been inactive for 7+ days.

**Architecture:** A new `RecordUserLastLoginListener` on Laravel's `Illuminate\Auth\Events\Login` records `last_login_at` on the `User` model and resets `last_login_reminder_sent_at`. A new `UserRepository` query finds eligible users. A new `UserInactivityReminderNotification` (extends the existing `AbstractUserNotification`, so it automatically gets an opt-out checkbox in the existing profile UI via `NotificationDiscoveryService`) sends a new `UserInactivityReminderMail`. A new `InactivityReminderService` orchestrates repository → notify → mark-sent, invoked by a new `NotifyInactiveUsersCommand`, scheduled daily.

**Tech Stack:** Laravel 11+ (`casts()` method style, not `$casts` property), Filament v4 notification/profile system, PHPUnit (`DatabaseTestCase`), Docker test runner with `--parallel`.

## Global Constraints

- `users` table gets two new nullable timestamp columns: `last_login_at`, `last_login_reminder_sent_at`. Neither is mass-assignable (`$fillable`); both go into `User::casts()` as `'datetime'`.
- `last_login_at` is updated on **every** login, across **both** guards (`web` and `standard`) — general-purpose tracking, not scoped to the reminder feature.
- Reminder eligibility (`UserRepository::getUsersEligibleForInactivityReminder(int $days = 7)`): `last_login_at` not null AND `<= now()-$days`; AND (`last_login_reminder_sent_at` null OR `<= now()-$days`); AND user has a role on the `standard` guard (`GuardEnum::STANDARD`).
- On login, `last_login_reminder_sent_at` is reset to `null` (via `User::recordLogin()`).
- `InactivityReminderService::notify()` calls the notification for **every** eligible user and always sets `last_login_reminder_sent_at = now()` afterwards, regardless of whether the user has opted out of the mail channel (`AbstractUserNotification::via()` filters the `mail` channel internally) — one simple code path, no special-casing in the service (per spec's explicit decision).
- `UserInactivityReminderNotification` **must override `channels()` to return `['mail']` only** — the `AbstractUserNotification` default `['mail', 'database']` would crash on the `database` channel since this notification implements no `toDatabase()`/`toArray()`.
- Class names follow ADR 0002 suffixes for all new code: `RecordUserLastLoginListener`, `InactivityReminderService`, `NotifyInactiveUsersCommand`, `UserInactivityReminderNotification`, `UserInactivityReminderMail`. Do not use existing suffix-less classes (`OfferNotifier`, `NotifyOffers`) as a naming precedent — they predate ADR 0002 (Rule 8 grandfathering).
- Non-fillable `User` field updates go through model methods using `setAttribute()` + `save()`, matching the existing `Assignment::setNotified()` / `Assignment::setExpiresAt()` convention — not `forceFill()`.
- Mail text must explicitly mention that the reminder can be disabled in the profile.
- Test layering (ADR 0001, verified against existing sibling tests in this codebase):
  - Listener → `tests/Integration/Listeners/`
  - Repository → `tests/Integration/Repository/`
  - Service → `tests/Integration/Services/`
  - Notification opt-out → `tests/Integration/Notifications/NotificationPreferencesTest.php` (existing shared file, add cases)
  - Mailable → `tests/Integration/Mail/`
  - Command → `tests/Feature/Console/`
- CHANGELOG.md entry under `## [Unreleased]`, in **English** (ADR 0006 — the whole file is English; do not write German entries).
- Scheduled `dailyAt('09:00')` in `routes/console.php`.

Reference spec: `docs/superpowers/specs/2026-08-15-last-login-inactivity-notification-design.md`

---

## Task 1: `users` table migration + `User` model tracking fields

**Files:**
- Create: `database/migrations/2026_08_15_120000_add_last_login_tracking_to_users_table.php`
- Modify: `app/Models/User.php`
- Test: `tests/Integration/Models/UserTest.php` (create if it doesn't already exist — check first; if it exists, add to it instead)

**Interfaces:**
- Produces: `users.last_login_at` (nullable timestamp, cast `datetime`), `users.last_login_reminder_sent_at` (nullable timestamp, cast `datetime`); `User::recordLogin(): void` (sets `last_login_at` to `now()`, `last_login_reminder_sent_at` to `null`, saves); `User::markInactivityReminderSent(): void` (sets `last_login_reminder_sent_at` to `now()`, saves). Later tasks call these two methods and rely on the two columns.

- [ ] **Step 1: Check for an existing `UserTest`**

Run: `find /home/ilya/PhpstormProjects/dashclip-delivery/tests -iname "UserTest.php"`

If a file is found at `tests/Integration/Models/UserTest.php` (or elsewhere), add the new test methods from Step 2 to that existing class instead of creating a new file, keeping its existing tests untouched. If none exists anywhere, create `tests/Integration/Models/UserTest.php` fresh as a new `final class UserTest extends DatabaseTestCase` in namespace `Tests\Integration\Models`.

- [ ] **Step 2: Write the failing tests**

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\DatabaseTestCase;

final class UserTest extends DatabaseTestCase
{
    public function testRecordLoginSetsLastLoginAtAndResetsReminderTimestamp(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $user = User::factory()->create();
        $user->markInactivityReminderSent();
        $this->assertNotNull($user->fresh()->last_login_reminder_sent_at);

        $user->recordLogin();

        $fresh = $user->fresh();
        $this->assertTrue($fresh->last_login_at->equalTo(Carbon::now()));
        $this->assertNull($fresh->last_login_reminder_sent_at);
    }

    public function testMarkInactivityReminderSentSetsTimestamp(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $user = User::factory()->create();
        $user->markInactivityReminderSent();

        $this->assertTrue($user->fresh()->last_login_reminder_sent_at->equalTo(Carbon::now()));
    }

    public function testLastLoginColumnsAreCastToDatetime(): void
    {
        $user = User::factory()->create([
            'last_login_at' => '2026-08-01 09:00:00',
            'last_login_reminder_sent_at' => '2026-08-05 09:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $user->last_login_at);
        $this->assertInstanceOf(Carbon::class, $user->last_login_reminder_sent_at);
    }
}
```

- [ ] **Step 3: Run the tests to verify they fail**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Models/UserTest.php`

Expected: FAIL — column `last_login_at` / method `recordLogin` do not exist yet.

- [ ] **Step 4: Create the migration**

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
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_login_reminder_sent_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'last_login_reminder_sent_at']);
        });
    }
};
```

- [ ] **Step 5: Add casts and the two model methods to `User`**

In `app/Models/User.php`, extend the existing `casts()` method (do not add a `$casts` property — this model uses the Laravel 11+ method style):

```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'app_authentication_secret' => 'encrypted',
        'app_authentication_recovery_codes' => 'encrypted:array',
        'has_email_authentication' => 'boolean',
        'onboarding_completed' => 'boolean',
        'last_login_at' => 'datetime',
        'last_login_reminder_sent_at' => 'datetime',
    ];
}
```

Add two new public methods anywhere among the other public methods (e.g. directly after `canAccessPanel()`):

```php
/**
 * Record that the user just logged in and reset any pending inactivity reminder cycle.
 */
public function recordLogin(): void
{
    $this->setAttribute('last_login_at', now());
    $this->setAttribute('last_login_reminder_sent_at', null);
    $this->save();
}

/**
 * Record that an inactivity reminder was (attempted to be) sent to the user.
 */
public function markInactivityReminderSent(): void
{
    $this->setAttribute('last_login_reminder_sent_at', now());
    $this->save();
}
```

Do **not** add `last_login_at` or `last_login_reminder_sent_at` to `$fillable` — both are system-managed only.

- [ ] **Step 6: Run the tests to verify they pass**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Models/UserTest.php`

Expected: PASS (3/3)

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_08_15_120000_add_last_login_tracking_to_users_table.php \
  app/Models/User.php tests/Integration/Models/UserTest.php
git commit -m "feat(users): add last-login tracking columns and model methods

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Task 2: `RecordUserLastLoginListener`

**Files:**
- Create: `app/Listeners/RecordUserLastLoginListener.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Integration/Listeners/RecordUserLastLoginListenerTest.php`

**Interfaces:**
- Consumes: `User::recordLogin(): void` (Task 1).
- Produces: `App\Listeners\RecordUserLastLoginListener::handle(Login $event): void`, registered on `Illuminate\Auth\Events\Login`. No later task depends on this listener directly — it's an event-driven side effect.

- [ ] **Step 1: Write the failing test**

Create `tests/Integration/Listeners/RecordUserLastLoginListenerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Listeners\RecordUserLastLoginListener;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Carbon;
use Tests\DatabaseTestCase;

final class RecordUserLastLoginListenerTest extends DatabaseTestCase
{
    public function testHandleRecordsLoginForUser(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $user = User::factory()->create();
        $user->markInactivityReminderSent();
        $this->assertNotNull($user->fresh()->last_login_reminder_sent_at);

        $event = new Login('standard', $user, false);
        (new RecordUserLastLoginListener())->handle($event);

        $fresh = $user->fresh();
        $this->assertTrue($fresh->last_login_at->equalTo(Carbon::now()));
        $this->assertNull($fresh->last_login_reminder_sent_at);
    }

    public function testHandleIgnoresNonUserNotifiables(): void
    {
        $notifiable = new class {
            public $id = 999;
        };

        $event = new Login('standard', $notifiable, false);

        // Must not throw
        (new RecordUserLastLoginListener())->handle($event);

        $this->assertTrue(true);
    }

    public function testLoginEventDispatchTriggersListener(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $user = User::factory()->create();

        event(new Login('standard', $user, false));

        $this->assertTrue($user->fresh()->last_login_at->equalTo(Carbon::now()));
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Listeners/RecordUserLastLoginListenerTest.php`

Expected: FAIL — `RecordUserLastLoginListener` does not exist yet; third test fails because nothing is listening to `Login` yet.

- [ ] **Step 3: Create the listener**

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

class RecordUserLastLoginListener
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (!$user instanceof User) {
            return;
        }

        $user->recordLogin();
    }
}
```

- [ ] **Step 4: Register the listener in `AppServiceProvider`**

In `app/Providers/AppServiceProvider.php`, add two imports near the existing `App\Listeners\*` / `Illuminate\*` imports:

```php
use App\Listeners\RecordUserLastLoginListener;
```

and

```php
use Illuminate\Auth\Events\Login;
```

Then, inside `boot()`, add a new `Event::listen(...)` call alongside the existing ones (e.g. right after the `ChannelVideoReceptionPaused` line):

```php
Event::listen(Login::class, RecordUserLastLoginListener::class);
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Listeners/RecordUserLastLoginListenerTest.php`

Expected: PASS (3/3)

- [ ] **Step 6: Commit**

```bash
git add app/Listeners/RecordUserLastLoginListener.php app/Providers/AppServiceProvider.php \
  tests/Integration/Listeners/RecordUserLastLoginListenerTest.php
git commit -m "feat(auth): record last login timestamp on every login

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Task 3: `UserRepository::getUsersEligibleForInactivityReminder()`

**Files:**
- Modify: `app/Repository/UserRepository.php`
- Test: `tests/Integration/Repository/UserRepositoryTest.php` (create if it doesn't exist — check first)

**Interfaces:**
- Produces: `UserRepository::getUsersEligibleForInactivityReminder(int $days = 7): Collection<User>`. Consumed by `InactivityReminderService` in Task 5 with the exact same signature.

- [ ] **Step 1: Check for an existing `UserRepositoryTest`**

Run: `find /home/ilya/PhpstormProjects/dashclip-delivery/tests -iname "UserRepositoryTest.php"`

If found, add the new test methods from Step 2 to that class. If not, create `tests/Integration/Repository/UserRepositoryTest.php` as a new `final class UserRepositoryTest extends DatabaseTestCase` in namespace `Tests\Integration\Repository`.

- [ ] **Step 2: Write the failing tests**

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Enum\Guard\GuardEnum;
use App\Models\User;
use App\Repository\UserRepository;
use Illuminate\Support\Carbon;
use Tests\DatabaseTestCase;

final class UserRepositoryTest extends DatabaseTestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(UserRepository::class);
    }

    public function testExcludesUserWhoNeverLoggedIn(): void
    {
        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => null,
        ]);

        $ids = $this->repository->getUsersEligibleForInactivityReminder(7)->pluck('id');

        $this->assertNotContains($user->getKey(), $ids->all());
    }

    public function testExcludesUserWhoLoggedInRecently(): void
    {
        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(3),
        ]);

        $ids = $this->repository->getUsersEligibleForInactivityReminder(7)->pluck('id');

        $this->assertNotContains($user->getKey(), $ids->all());
    }

    public function testIncludesInactiveUserWithNoPriorReminder(): void
    {
        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(8),
            'last_login_reminder_sent_at' => null,
        ]);

        $ids = $this->repository->getUsersEligibleForInactivityReminder(7)->pluck('id');

        $this->assertContains($user->getKey(), $ids->all());
    }

    public function testExcludesUserRemindedRecently(): void
    {
        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(10),
            'last_login_reminder_sent_at' => now()->subDays(2),
        ]);

        $ids = $this->repository->getUsersEligibleForInactivityReminder(7)->pluck('id');

        $this->assertNotContains($user->getKey(), $ids->all());
    }

    public function testIncludesUserRemindedLongAgo(): void
    {
        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(20),
            'last_login_reminder_sent_at' => now()->subDays(9),
        ]);

        $ids = $this->repository->getUsersEligibleForInactivityReminder(7)->pluck('id');

        $this->assertContains($user->getKey(), $ids->all());
    }

    public function testExcludesUserWithoutStandardGuardRole(): void
    {
        $user = User::factory()->admin(GuardEnum::DEFAULT)->create([
            'last_login_at' => now()->subDays(10),
        ]);

        $ids = $this->repository->getUsersEligibleForInactivityReminder(7)->pluck('id');

        $this->assertNotContains($user->getKey(), $ids->all());
    }
}
```

Note: `Carbon` is imported but unused if you don't need `Carbon::setTestNow()` for these cases (relative `now()->subDays(...)` is enough) — remove the unused import if your IDE/linter flags it, or keep it only if you add time-freezing. Prefer removing it if unused.

- [ ] **Step 3: Run the tests to verify they fail**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Repository/UserRepositoryTest.php`

Expected: FAIL — `getUsersEligibleForInactivityReminder` does not exist yet.

- [ ] **Step 4: Implement the repository method**

In `app/Repository/UserRepository.php`, add imports:

```php
use App\Enum\Guard\GuardEnum;
use Illuminate\Database\Eloquent\Builder;
```

Add the method (anywhere among the other public methods):

```php
/**
 * Users eligible for the inactivity reminder: have logged in at least once, their last login is
 * at least $days ago, no reminder was sent in the last $days (or none yet), and they hold a role
 * on the standard-panel guard (excludes pure admin-only accounts).
 *
 * @return Collection<User>
 */
public function getUsersEligibleForInactivityReminder(int $days = 7): Collection
{
    $threshold = now()->subDays($days);

    return User::query()
        ->whereNotNull('last_login_at')
        ->where('last_login_at', '<=', $threshold)
        ->where(function (Builder $query) use ($threshold) {
            $query->whereNull('last_login_reminder_sent_at')
                ->orWhere('last_login_reminder_sent_at', '<=', $threshold);
        })
        ->whereHas('roles', fn (Builder $query) => $query->where('guard_name', GuardEnum::STANDARD->value))
        ->get();
}
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Repository/UserRepositoryTest.php`

Expected: PASS (6/6)

- [ ] **Step 6: Commit**

```bash
git add app/Repository/UserRepository.php tests/Integration/Repository/UserRepositoryTest.php
git commit -m "feat(users): add repository query for inactivity-reminder eligibility

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Task 4: Notification, Mail, view, and translations

**Files:**
- Create: `app/Notifications/UserInactivityReminderNotification.php`
- Create: `app/Mail/UserInactivityReminderMail.php`
- Create: `resources/views/emails/user-inactivity-reminder.blade.php`
- Modify: `lang/de/notifications.php`, `lang/en/notifications.php`
- Modify: `lang/de/mails.php`, `lang/en/mails.php`
- Modify: `tests/Integration/Notifications/NotificationPreferencesTest.php`
- Test: `tests/Integration/Mail/UserInactivityReminderMailTest.php`

**Interfaces:**
- Consumes: nothing from earlier tasks directly (this task is self-contained data/presentation).
- Produces: `App\Notifications\UserInactivityReminderNotification::__construct(CarbonInterface $lastLoginAt)`, `->toMail(User $notifiable): UserInactivityReminderMail`. Task 5's `NotificationService::notifyUserInactivity()` constructs and sends this exact class with this exact constructor signature.

- [ ] **Step 1: Write the failing Mailable test**

Create `tests/Integration/Mail/UserInactivityReminderMailTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Mail;

use App\Mail\UserInactivityReminderMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\DatabaseTestCase;

final class UserInactivityReminderMailTest extends DatabaseTestCase
{
    public function testEnvelopeSubjectIsSet(): void
    {
        $user = User::factory()->create();
        $lastLoginAt = Carbon::now()->subDays(10);

        $mail = new UserInactivityReminderMail($user, $lastLoginAt);

        $this->assertSame(
            __('mails.user_inactivity_reminder.subject'),
            $mail->envelope()->subject
        );
    }

    public function testViewDataContainsExpectedKeys(): void
    {
        $user = User::factory()->create();
        $lastLoginAt = Carbon::now()->subDays(10);

        $mail = new UserInactivityReminderMail($user, $lastLoginAt);
        $data = $mail->content()->with;

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('lastLoginAt', $data);
        $this->assertArrayHasKey('loginUrl', $data);
        $this->assertTrue($data['user']->is($user));
        $this->assertTrue($data['lastLoginAt']->equalTo($lastLoginAt));
    }

    public function testRenderedBodyMentionsProfileOptOut(): void
    {
        $user = User::factory()->create();
        $lastLoginAt = Carbon::now()->subDays(10);

        $mail = new UserInactivityReminderMail($user, $lastLoginAt);
        $html = $mail->render();

        $this->assertStringContainsString(
            __('mails.user_inactivity_reminder.opt_out_hint'),
            $html
        );
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Mail/UserInactivityReminderMailTest.php`

Expected: FAIL — `UserInactivityReminderMail` does not exist yet.

- [ ] **Step 3: Add translations**

In `lang/de/notifications.php`, add the import and the new `mail.types` entry:

```php
use App\Notifications\UserInactivityReminderNotification;
```

(add alongside the existing `use` statements), and inside the `'types' => [...]` array:

```php
UserInactivityReminderNotification::class => 'Erinnern, wenn ich länger nicht eingeloggt war',
```

In `lang/en/notifications.php`, same import, and:

```php
UserInactivityReminderNotification::class => 'Remind me when I haven\'t logged in for a while',
```

In `lang/de/mails.php`, add a new top-level entry (alongside `channel_reception_paused` etc.):

```php
'user_inactivity_reminder' => [
    'subject' => 'Du warst länger nicht mehr da',
    'headline' => 'Wir haben dich vermisst',
    'greeting' => 'Hallo :name,',
    'body' => 'du hast dich seit dem :date nicht mehr bei :app eingeloggt. Schau doch mal wieder vorbei!',
    'cta' => 'Jetzt einloggen',
    'opt_out_hint' => 'Diese Erinnerung kannst du jederzeit in deinem Profil unter "Benachrichtigungen per E-Mail" abstellen.',
    'signature' => 'Viele Grüße<br>Dein :app-Team',
],
```

In `lang/en/mails.php`:

```php
'user_inactivity_reminder' => [
    'subject' => 'We haven\'t seen you in a while',
    'headline' => 'We missed you',
    'greeting' => 'Hi :name,',
    'body' => 'you haven\'t logged in to :app since :date. Come back and check what\'s new!',
    'cta' => 'Log in now',
    'opt_out_hint' => 'You can turn off this reminder any time in your profile under "Notifications per Mail".',
    'signature' => 'Best regards<br>Your :app team',
],
```

- [ ] **Step 4: Create the Notification class**

```php
<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\UserInactivityReminderMail;
use App\Models\User;
use App\Notifications\Contracts\HasToMailContract;
use Carbon\CarbonInterface;

class UserInactivityReminderNotification extends AbstractUserNotification implements HasToMailContract
{
    public function __construct(public readonly CarbonInterface $lastLoginAt)
    {
    }

    protected function channels(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): UserInactivityReminderMail
    {
        return new UserInactivityReminderMail($notifiable, $this->lastLoginAt);
    }
}
```

- [ ] **Step 5: Create the Mailable class**

```php
<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class UserInactivityReminderMail extends AbstractLoggedMail
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public CarbonInterface $lastLoginAt,
    ) {
        $this->subjectLine = __('mails.user_inactivity_reminder.subject');
    }

    protected function viewName(): string
    {
        return 'emails.user-inactivity-reminder';
    }

    protected function viewData(): array
    {
        return [
            'user' => $this->user,
            'lastLoginAt' => $this->lastLoginAt,
            'loginUrl' => route('filament.standard.auth.login'),
        ];
    }
}
```

- [ ] **Step 6: Create the Blade view**

Create `resources/views/emails/user-inactivity-reminder.blade.php`:

```blade
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ __('mails.user_inactivity_reminder.subject') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:Arial, sans-serif;">
@include('emails.partials.header')
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="max-width:600px; width:100%; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:6px;">
    <tr>
        <td style="padding:24px; color:#0f172a; line-height:1.6; font-size:16px;">
            <h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700;">
                {{ __('mails.user_inactivity_reminder.headline') }}
            </h1>
            <p style="margin:0 0 16px 0;">
                {!! __('mails.user_inactivity_reminder.greeting', ['name' => $user->name]) !!}
            </p>
            <p style="margin:0 0 20px 0;">
                {{ __('mails.user_inactivity_reminder.body', [
                    'date' => $lastLoginAt->timezone(config('app.timezone'))->format('d.m.Y'),
                    'app' => config('app.name'),
                ]) }}
            </p>
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px 0;">
                <tr>
                    <td align="center" style="border-radius:4px; background:#22c55e;">
                        <a href="{{ $loginUrl }}" target="_blank"
                           style="display:inline-block; padding:12px 20px; font-size:14px; font-weight:700; color:#ffffff; text-decoration:none;">
                            {{ __('mails.user_inactivity_reminder.cta') }}
                        </a>
                    </td>
                </tr>
            </table>
            <p style="margin:0 0 20px 0; font-size:13px; color:#64748b;">
                {{ __('mails.user_inactivity_reminder.opt_out_hint') }}
            </p>
            <p style="margin:0 0 24px 0;">
                {!! __('mails.user_inactivity_reminder.signature', ['app' => config('app.name')]) !!}
            </p>
        </td>
    </tr>
</table>
@include('emails.partials.footer')
</body>
</html>
```

- [ ] **Step 7: Run the Mailable test to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Mail/UserInactivityReminderMailTest.php`

Expected: PASS (3/3)

- [ ] **Step 8: Add opt-out coverage to the shared `NotificationPreferencesTest`**

Add these two methods to `tests/Integration/Notifications/NotificationPreferencesTest.php` (add the import `use App\Notifications\UserInactivityReminderNotification;` and `use Illuminate\Support\Carbon;` at the top alongside the existing ones):

```php
public function testInactivityReminderMailChannelIsRemovedWhenOptedOut(): void
{
    $user = User::factory()->create();
    $repository = app(UserMailConfigRepository::class);
    $repository->setForUser($user, UserInactivityReminderNotification::class, false);

    $notification = new UserInactivityReminderNotification(Carbon::now()->subDays(10));

    $channels = $notification->via($user);

    $this->assertNotContains('mail', $channels);
}

public function testInactivityReminderMailChannelRespectsDefaultOptIn(): void
{
    $user = User::factory()->create();
    $notification = new UserInactivityReminderNotification(Carbon::now()->subDays(10));

    $channels = $notification->via($user);

    $this->assertContains('mail', $channels);
}
```

- [ ] **Step 9: Run the full `NotificationPreferencesTest` file to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Notifications/NotificationPreferencesTest.php`

Expected: PASS (4/4 — 2 existing + 2 new)

- [ ] **Step 10: Commit**

```bash
git add app/Notifications/UserInactivityReminderNotification.php app/Mail/UserInactivityReminderMail.php \
  resources/views/emails/user-inactivity-reminder.blade.php \
  lang/de/notifications.php lang/en/notifications.php lang/de/mails.php lang/en/mails.php \
  tests/Integration/Mail/UserInactivityReminderMailTest.php \
  tests/Integration/Notifications/NotificationPreferencesTest.php
git commit -m "feat(notifications): add opt-outable inactivity reminder notification and mail

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Task 5: `NotificationService` method, `InactivityReminderService`, `NotifyInactiveUsersCommand`, schedule

**Files:**
- Modify: `app/Services/NotificationService.php`
- Create: `app/Services/InactivityReminderService.php`
- Create: `app/Console/Commands/NotifyInactiveUsersCommand.php`
- Modify: `routes/console.php`
- Modify: `tests/Integration/Services/NotificationServiceTest.php`
- Test: `tests/Integration/Services/InactivityReminderServiceTest.php`
- Test: `tests/Feature/Console/NotifyInactiveUsersCommandTest.php`

**Interfaces:**
- Consumes: `UserRepository::getUsersEligibleForInactivityReminder(int $days = 7): Collection<User>` (Task 3), `UserInactivityReminderNotification::__construct(CarbonInterface $lastLoginAt)` (Task 4), `User::markInactivityReminderSent(): void` (Task 1).
- Produces: `NotificationService::notifyUserInactivity(User $user, CarbonInterface $lastLoginAt): void`; `InactivityReminderService::notify(int $days = 7): int`; console command `notify:inactive-users {--days=7}`.

- [ ] **Step 1: Write the failing `NotificationServiceTest` addition**

Add this method to `tests/Integration/Services/NotificationServiceTest.php` (add `use App\Notifications\UserInactivityReminderNotification;` and `use Illuminate\Support\Carbon;` to the existing imports):

```php
public function testItSendsInactivityReminderNotificationToUser(): void
{
    $user = User::factory()->create();
    $lastLoginAt = Carbon::now()->subDays(10);

    $this->notificationService->notifyUserInactivity($user, $lastLoginAt);

    Notification::assertSentTo(
        $user,
        UserInactivityReminderNotification::class,
        function (UserInactivityReminderNotification $notification) use ($lastLoginAt) {
            return $notification->lastLoginAt->equalTo($lastLoginAt);
        }
    );
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Services/NotificationServiceTest.php`

Expected: FAIL — `notifyUserInactivity` does not exist yet (the other, pre-existing methods in this file still pass).

- [ ] **Step 3: Add the method to `NotificationService`**

In `app/Services/NotificationService.php`, add the import `use App\Notifications\UserInactivityReminderNotification;` and `use Carbon\CarbonInterface;`, then add:

```php
/**
 * Send the inactivity reminder notification to the user.
 * @param User $user
 * @param CarbonInterface $lastLoginAt
 * @return void
 */
public function notifyUserInactivity(User $user, CarbonInterface $lastLoginAt): void
{
    $user->notify(new UserInactivityReminderNotification($lastLoginAt));
}
```

- [ ] **Step 4: Run `NotificationServiceTest` to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Services/NotificationServiceTest.php`

Expected: PASS (6/6 — 5 existing + 1 new)

- [ ] **Step 5: Write the failing `InactivityReminderServiceTest`**

Create `tests/Integration/Services/InactivityReminderServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Enum\Guard\GuardEnum;
use App\Models\User;
use App\Notifications\UserInactivityReminderNotification;
use App\Repository\UserMailConfigRepository;
use App\Services\InactivityReminderService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\DatabaseTestCase;

final class InactivityReminderServiceTest extends DatabaseTestCase
{
    private InactivityReminderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->service = $this->app->make(InactivityReminderService::class);
    }

    public function testNotifiesEligibleUserAndMarksReminderSent(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(8),
        ]);

        $count = $this->service->notify(7);

        $this->assertSame(1, $count);
        Notification::assertSentTo($user, UserInactivityReminderNotification::class);
        $this->assertTrue($user->fresh()->last_login_reminder_sent_at->equalTo(Carbon::now()));
    }

    public function testDoesNotNotifyRecentlyActiveUser(): void
    {
        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(2),
        ]);

        $count = $this->service->notify(7);

        $this->assertSame(0, $count);
        Notification::assertNotSentTo($user, UserInactivityReminderNotification::class);
    }

    public function testStillMarksReminderSentWhenUserOptedOut(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(8),
        ]);
        app(UserMailConfigRepository::class)->setForUser(
            $user,
            UserInactivityReminderNotification::class,
            false
        );

        $this->service->notify(7);

        $this->assertTrue($user->fresh()->last_login_reminder_sent_at->equalTo(Carbon::now()));
    }
}
```

- [ ] **Step 6: Run it to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Services/InactivityReminderServiceTest.php`

Expected: FAIL — `InactivityReminderService` does not exist yet.

- [ ] **Step 7: Create `InactivityReminderService`**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\UserRepository;

class InactivityReminderService
{
    public function __construct(
        private UserRepository $userRepository,
        private NotificationService $notificationService,
    ) {
    }

    /**
     * Notify all eligible users about their inactivity and record that a reminder was sent.
     * @param int $days
     * @return int Number of users processed.
     */
    public function notify(int $days = 7): int
    {
        $users = $this->userRepository->getUsersEligibleForInactivityReminder($days);

        foreach ($users as $user) {
            $this->notificationService->notifyUserInactivity($user, $user->last_login_at);
            $user->markInactivityReminderSent();
        }

        return $users->count();
    }
}
```

- [ ] **Step 8: Run `InactivityReminderServiceTest` to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Integration/Services/InactivityReminderServiceTest.php`

Expected: PASS (3/3)

- [ ] **Step 9: Write the failing `NotifyInactiveUsersCommandTest`**

Create `tests/Feature/Console/NotifyInactiveUsersCommandTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enum\Guard\GuardEnum;
use App\Models\User;
use App\Notifications\UserInactivityReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Tests\DatabaseTestCase;

final class NotifyInactiveUsersCommandTest extends DatabaseTestCase
{
    public function testCommandNotifiesEligibleUsers(): void
    {
        Notification::fake();

        $eligible = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(8),
        ]);
        $active = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDay(),
        ]);

        $this->artisan('notify:inactive-users')
            ->assertExitCode(Command::SUCCESS);

        Notification::assertSentTo($eligible, UserInactivityReminderNotification::class);
        Notification::assertNotSentTo($active, UserInactivityReminderNotification::class);
    }

    public function testCommandRespectsCustomDaysOption(): void
    {
        Notification::fake();

        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(4),
        ]);

        $this->artisan('notify:inactive-users --days=3')
            ->assertExitCode(Command::SUCCESS);

        Notification::assertSentTo($user, UserInactivityReminderNotification::class);
    }
}
```

- [ ] **Step 10: Run it to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Console/NotifyInactiveUsersCommandTest.php`

Expected: FAIL — command `notify:inactive-users` does not exist yet.

- [ ] **Step 11: Create `NotifyInactiveUsersCommand`**

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\InactivityReminderService;
use Illuminate\Console\Command;

class NotifyInactiveUsersCommand extends Command
{
    protected $signature = 'notify:inactive-users {--days=7}';
    protected $description = 'Sends an inactivity reminder to users who have not logged in for a while.';

    public function __construct(private InactivityReminderService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $count = $this->service->notify((int) $this->option('days'));
        $this->info("Inactivity reminders sent: {$count}");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 12: Register the schedule**

In `routes/console.php`, add a new section (e.g. right after the `# general` block at the top):

```php
# Inactivity
Schedule::command(Commands\NotifyInactiveUsersCommand::class)->dailyAt('09:00');
```

- [ ] **Step 13: Run `NotifyInactiveUsersCommandTest` to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel tests/Feature/Console/NotifyInactiveUsersCommandTest.php`

Expected: PASS (2/2)

- [ ] **Step 14: Commit**

```bash
git add app/Services/NotificationService.php app/Services/InactivityReminderService.php \
  app/Console/Commands/NotifyInactiveUsersCommand.php routes/console.php \
  tests/Integration/Services/NotificationServiceTest.php \
  tests/Integration/Services/InactivityReminderServiceTest.php \
  tests/Feature/Console/NotifyInactiveUsersCommandTest.php
git commit -m "feat(users): add scheduled command sending inactivity reminders

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Task 6: CHANGELOG entry

**Files:**
- Modify: `CHANGELOG.md`

**Interfaces:** none — documentation only.

- [ ] **Step 1: Add the `[Unreleased]` entry**

In `CHANGELOG.md`, under `## [Unreleased]`, add (create the `### Added` subsection if none exists yet at that point — check the current file state first, since Task order across features may have already added one):

```markdown
### Added
- **Last-login tracking & inactivity reminder**
    - the app now records each user's last login timestamp (across both the admin and standard
      panels); users who have not logged in for 7+ days receive a reminder email, repeated every
      7 days while inactivity continues, resettable by logging in again, and toggleable per-user
      in the profile's "Notifications per Mail" settings like any other notification.
```

- [ ] **Step 2: Verify the file renders sensibly**

Read the top of `CHANGELOG.md` to confirm the new entry sits correctly under `## [Unreleased]`, in English, matching the surrounding house style (bold sub-heading, indented bullet).

- [ ] **Step 3: Commit**

```bash
git add CHANGELOG.md
git commit -m "docs(changelog): add last-login tracking and inactivity reminder entry

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Self-Review Notes

- **Spec coverage:** migration + model tracking fields (Task 1), login listener (Task 2), eligibility query (Task 3), notification/mail/translations/opt-out (Task 4), service/command/schedule (Task 5), changelog (Task 6) — every spec section has a task.
- **Placeholder scan:** no TBD/TODO; every step has runnable code.
- **Type consistency:** `getUsersEligibleForInactivityReminder(int $days = 7): Collection` (Task 3) is called identically in `InactivityReminderService::notify()` (Task 5); `UserInactivityReminderNotification::__construct(CarbonInterface $lastLoginAt)` (Task 4) is constructed identically in `NotificationService::notifyUserInactivity()` (Task 5); `User::recordLogin()` / `User::markInactivityReminderSent()` (Task 1) are called with matching names in Tasks 2 and 5.
- **channels() override:** explicitly included in Task 4 Step 4 to prevent the inherited `['mail', 'database']` default from crashing on the unimplemented `toDatabase()`/`toArray()`.
