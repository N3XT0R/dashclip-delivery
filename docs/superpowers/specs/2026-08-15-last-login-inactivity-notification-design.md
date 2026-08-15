# Design: Last-Login-Tracking & Inaktivitäts-Erinnerungsmail

## Kontext

Zwei zusammenhängende Anforderungen:

1. Erfassen, wann sich ein User zuletzt eingeloggt hat.
2. War ein User (Channel-Betreiber oder regulärer User, kein reiner Admin-Account) länger als 7
   Tage nicht mehr eingeloggt, soll er eine Erinnerungsmail bekommen. Die Mail wiederholt sich alle
   weiteren 7 Tage, solange die Inaktivität anhält, und der Zyklus setzt sich beim nächsten Login
   zurück. Der User kann diese Mail über sein Profil abstellen — nach demselben Muster wie die dort
   bereits gelisteten Mails —, und der Mailtext weist explizit darauf hin.

Bestehende Referenzen im Code:

- `App\Notifications\AbstractUserNotification` — Basisklasse für nutzerkonfigurierbare
  Mail-Benachrichtigungen; `via()` filtert den `mail`-Kanal heraus, wenn
  `UserMailConfigRepository::isAllowed()` `false` liefert.
- `App\Services\Notifications\NotificationDiscoveryService` (+ `CachedNotificationDiscoveryService`
  Decorator, Facade `App\Facades\NotificationDiscovery`) — scannt `app/Notifications/*Notification.php`
  nach Unterklassen von `AbstractUserNotification` und liefert die Liste an
  `App\Filament\Admin\Pages\Auth\EditProfile` (registriert per `->profile(EditProfile::class)` **für
  beide Panels**, Standard-Panel-Route `standard/profile` zeigt auf dieselbe Klasse). Jede neue
  `AbstractUserNotification`-Unterklasse erscheint dadurch automatisch als Checkbox im Profil unter
  „Benachrichtigungen per E-Mail" — ohne zusätzliche UI-Arbeit.
- `App\Repository\UserMailConfigRepository` — persistiert die Opt-in/Opt-out-Entscheidung pro
  `(user_id, notification_class)`-Paar.
- `App\Services\NotificationService` — bestehende Service-Klasse mit einer Methode pro
  Notification-Anwendungsfall (`notifyChannelAccessApproved`, `notifyDuplicatedUpload`,
  `notifyUserVideoUploadProceeded`), jeweils `$user->notify(new XNotification(...))`.
- `App\Services\OfferNotifier` — Vorbild für den Aufbau eines periodischen
  „finde-Betroffene→benachrichtige→markiere"-Services (predates ADR 0002, daher ohne `*Service`-Suffix
  — Bestandsschutz, siehe ADR 0002 Rule 8).
- `App\Console\Commands\NotifyOffers` + `routes/console.php` — Vorbild für dünne Command-Entrypoints,
  die an einen Service delegieren, und für `Schedule::command(...)`-Registrierung.
- `App\Repository\UserRepository` — bestehende Repository-Klasse für `User`-Queries.
- `App\Enum\Users\RoleEnum` (`SUPER_ADMIN`, `REGULAR`, `CHANNEL_OPERATOR`) und
  `App\Enum\Guard\GuardEnum` (`DEFAULT = 'web'`, `STANDARD = 'standard'`) — `REGULAR` und
  `CHANNEL_OPERATOR` sind Rollen auf dem `standard`-Guard; `UserObserver` weist jedem neuen User
  standardmäßig `REGULAR` auf `standard` zu.
- `lang/de/notifications.php` / `lang/en/notifications.php` — Struktur `mail.types.{FQCN}` für die
  Checkbox-Labels; `lang/de/mails.php` / `lang/en/mails.php` — Struktur pro Mail mit `subject`,
  `headline`, `greeting`, `body`, `signature` etc.
- `resources/views/emails/partials/{header,footer}.blade.php` — gemeinsames Mail-Layout.

**ADR-Bezug:**

- **ADR 0001** (Test-Layering): Listener-, Repository- und Service-Tests → `tests/Integration/...`
  (Vorbild: `tests/Integration/Listeners/SendChannelVideoReceptionPausedMailTest.php`,
  `tests/Integration/Repository/...`, `tests/Integration/Services/OfferNotifierTest.php`).
  Command-Test → `tests/Feature/Console/...` (Vorbild: `NotifyOffersTest.php`, `AssignExpireTest.php`).
- **ADR 0002** (Suffix-Konvention): `*Command`, `*Listener`, `*Service`, `*Notification`, `*Mail`
  sind für neuen Code verbindlich (nicht nur Empfehlung) — `OfferNotifier` selbst ist Altbestand und
  kein Vorbild für die Benennung neuer Klassen.
- **ADR 0003** (SOLID/Patterns): Command bleibt dünner Entrypoint und delegiert an einen Service;
  Query-Konstruktion bleibt im `UserRepository`; Notification-Versand nutzt die bestehende
  `NotificationService` statt eine Parallelstruktur aufzubauen (DRY, Rule 1–3). Kein neues Interface,
  da nur eine Implementierung existiert (Rule 6, YAGNI).
- **ADR 0005** (PHPDoc): Die neue Repository-Methode bekommt einen PHPDoc-Block, der die
  Eligibility-Kriterien erklärt (aus der Signatur allein nicht ersichtlich) und `Collection<User>`
  referenziert.
- **ADR 0006** (Changelog): Eintrag unter `## [Unreleased]`, auf Englisch (siehe gelernte Lektion aus
  dem vorigen Feature).

## Datenmodell

Neue Migration `database/migrations/2026_08_15_120000_add_last_login_tracking_to_users_table.php`,
fügt der `users`-Tabelle zwei nullable Timestamp-Spalten hinzu:

- `last_login_at` — zuletzt erfolgreicher Login, über **beide** Guards (`web` und `standard`)
  aktualisiert; rein informativer Tracking-Wert, unabhängig von der Erinnerungs-Logik.
- `last_login_reminder_sent_at` — wann zuletzt eine Inaktivitäts-Erinnerung verschickt wurde; wird bei
  jedem Login wieder auf `null` gesetzt.

```php
Schema::table('users', static function (Blueprint $table) {
    $table->timestamp('last_login_at')->nullable();
    $table->timestamp('last_login_reminder_sent_at')->nullable();
});
```

Beide Spalten sind **nicht** in `User::$fillable` — sie werden ausschließlich systemseitig gesetzt
(Listener bzw. Service), nicht per Mass-Assignment. `User::$casts` bekommt beide als `datetime`.

## Login-Tracking

Neuer Listener `App\Listeners\RecordUserLastLoginListener`, reagiert auf Laravels Standard-Event
`Illuminate\Auth\Events\Login` (wird von Filaments Login-Flow über den jeweiligen Guard ausgelöst,
für Admin- wie Standard-Panel):

```php
final class RecordUserLastLoginListener
{
    public function handle(Login $event): void
    {
        $user = $event->user;
        if (!$user instanceof User) {
            return;
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_reminder_sent_at' => null,
        ])->save();
    }
}
```

Registrierung analog zu den bestehenden Listenern in `AppServiceProvider::boot()`:

```php
Event::listen(Login::class, RecordUserLastLoginListener::class);
```

## Eligibility-Query

Neue Methode in `App\Repository\UserRepository`:

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

## Notification + Mail

Neue `App\Notifications\UserInactivityReminderNotification extends AbstractUserNotification
implements HasToMailContract`:

```php
class UserInactivityReminderNotification extends AbstractUserNotification implements HasToMailContract
{
    public function __construct(public readonly CarbonInterface $lastLoginAt)
    {
    }

    public function toMail(User $notifiable): UserInactivityReminderMail
    {
        return new UserInactivityReminderMail($notifiable, $this->lastLoginAt);
    }
}
```

(Kein `HasToDatabaseContract` — reine Mail-Erinnerung, kein Glocken-Eintrag nötig, da der User per
Definition gerade nicht eingeloggt ist; hält die Klasse minimal, YAGNI.)

Neue `App\Mail\UserInactivityReminderMail extends AbstractLoggedMail` (analog
`UserUploadProceedMail`), View `emails.user-inactivity-reminder`. Der Mailtext enthält explizit den
Hinweis, dass die Erinnerung im Profil abstellbar ist (Nutzer-Vorgabe).

Eintrag in `lang/de/notifications.php` / `lang/en/notifications.php` unter `mail.types`:

```php
UserInactivityReminderNotification::class => 'Erinnern, wenn ich länger nicht eingeloggt war',
// en: 'Remind me when I haven't logged in for a while',
```

→ erscheint dadurch automatisch als Checkbox im Profil (kein weiterer UI-Code nötig).

Neuer Eintrag in `lang/de/mails.php` / `lang/en/mails.php` unter `user_inactivity_reminder` mit
`subject`, `headline`, `greeting`, `body` (inkl. Zeitraum seit letztem Login), `cta` (Login-Link),
`opt_out_hint` (Hinweis aufs Profil), `signature` — Struktur wie `channel_reception_paused`.

Neue View `resources/views/emails/user-inactivity-reminder.blade.php`, nutzt
`emails.partials.header`/`footer` wie bestehende Mails, Login-Button verlinkt auf
`route('filament.standard.auth.login')`.

## Service + Command

Erweiterung von `App\Services\NotificationService` um eine weitere Einzel-User-Methode (folgt dem
bestehenden Muster der Klasse):

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

Neuer `App\Services\InactivityReminderService` (Aufbau analog `OfferNotifier`: finde
Betroffene → benachrichtige → markiere):

```php
class InactivityReminderService
{
    public function __construct(
        private UserRepository $userRepository,
        private NotificationService $notificationService,
    ) {
    }

    /**
     * Notify all eligible users about their inactivity and record that a reminder was sent.
     * @return int Number of users notified.
     */
    public function notify(int $days = 7): int
    {
        $users = $this->userRepository->getUsersEligibleForInactivityReminder($days);

        foreach ($users as $user) {
            $this->notificationService->notifyUserInactivity($user, $user->last_login_at);
            $user->forceFill(['last_login_reminder_sent_at' => now()])->save();
        }

        return $users->count();
    }
}
```

Neuer `App\Console\Commands\NotifyInactiveUsersCommand` (dünner Entrypoint):

```php
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

In `routes/console.php`, neue Sektion:

```php
# Inactivity
Schedule::command(Commands\NotifyInactiveUsersCommand::class)->dailyAt('09:00');
```

## Entscheidung: Opt-out-Fall

Wenn ein User die Erinnerung im Profil deaktiviert hat, wird `last_login_reminder_sent_at` trotzdem
bei jedem Lauf gesetzt — der Service ruft `notifyUserInactivity()` für jeden eligible User auf,
unabhängig vom Opt-out-Status; `AbstractUserNotification::via()` filtert den `mail`-Kanal intern
heraus, wenn `UserMailConfigRepository::isAllowed()` `false` liefert. Der Timestamp bedeutet damit
„der Service hat diesen User als fällig behandelt", nicht zwingend „eine Mail wurde tatsächlich
zugestellt" — bewusst in Kauf genommen für einen einzigen, einfachen Codepfad ohne Sonderfall-Logik
im Service selbst.

## Tests

- `tests/Integration/Listeners/RecordUserLastLoginListenerTest.php` — Login-Event setzt
  `last_login_at` und setzt `last_login_reminder_sent_at` zurück; ignoriert Non-User-Notifiables.
- `tests/Integration/Repository/UserRepositoryTest.php` (Ergänzung) — Fälle: nie eingeloggt
  (ausgeschlossen), Login vor < 7 Tagen (ausgeschlossen), Login vor ≥ 7 Tagen ohne bisherige
  Erinnerung (eingeschlossen), Erinnerung vor < 7 Tagen verschickt (ausgeschlossen), Erinnerung vor
  ≥ 7 Tagen verschickt (wieder eingeschlossen), User ausschließlich mit `SUPER_ADMIN`-Rolle auf
  `web`-Guard ohne Standard-Guard-Rolle (ausgeschlossen).
- `tests/Integration/Services/InactivityReminderServiceTest.php` — verschickt Notification nur an
  eligible User, setzt `last_login_reminder_sent_at` in jedem Fall (auch wenn der User die Mail per
  Profil-Opt-out deaktiviert hat, siehe Entscheidung oben), respektiert das Opt-out über
  `UserMailConfigRepository`/`AbstractUserNotification::via()` beim tatsächlichen Mail-Versand.
- `tests/Feature/Console/NotifyInactiveUsersCommandTest.php` — Command ruft Service auf, meldet
  Anzahl.
- Mailable-Test für `UserInactivityReminderMail` (Rendering, Betreff, Opt-out-Hinweis im Body).
- `NotificationDiscoveryServiceTest` benötigt keine Änderung (kein hartcodierter Klassenlisten-Test).

## Out of Scope

- Kein Tracking von Login-Historie (nur der letzte Zeitpunkt, keine Liste vergangener Logins).
- Keine Admin-UI zum manuellen Auslösen der Erinnerung.
- Keine Änderung an `AbstractUserNotification` oder der bestehenden Profil-Checkbox-UI selbst.
