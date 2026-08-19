# Design: User-Sprachpräferenz (Profil-Sprachwechsel)

## Kontext

Nutzer sollen im Profil ihre bevorzugte Sprache auswählen können. Diese Präferenz soll sowohl die
Filament-Oberfläche (Admin- und Standard-Panel) als auch ausgehende Mails/Notifications betreffen.

Bestehende Referenzen im Code:

- `App\Models\User implements ... HasLocalePreference` — das Interface ist bereits eingehängt,
  `preferredLocale(): string` existiert aber nur als Stub, der immer `config('app.locale')`
  zurückgibt, unabhängig vom User (`app/Models/User.php:268-271`). Laravels
  `Illuminate\Notifications\Notification`/`Mailable`-Versand nutzt `HasLocalePreference`
  automatisch, um Mails in der bevorzugten Sprache des Empfängers zu rendern — das greift also
  bereits an der richtigen Stelle, sobald `preferredLocale()` echte Werte liefert.
- Kein bestehender Locale-Switching-Mechanismus für die UI: kein `App::setLocale()`-Aufruf im
  gesamten Code, kein `app/Http/Middleware`-Verzeichnis.
- `App\Services\Notifications\NotificationDiscoveryService` — Vorbild für dynamische
  Verzeichnis-Discovery (`scandir()` über `app_path('Notifications')`), hier als Vorbild für die
  dynamische Sprach-Discovery über `lang_path()`.
- `lang/` enthält aktuell `de/`, `en/` und `vendor/` (published Vendor-Übersetzungen, u.a.
  `filament-panels` mit vielen Sprachen inkl. de/en) — die App-eigene Sprachliste ergibt sich aus
  den Top-Level-Verzeichnissen in `lang/` **ohne** `vendor`.
- `App\Filament\Admin\Pages\Auth\EditProfile` — bereits geteilte Profilseite für **beide** Panels
  (`->profile(EditProfile::class)` in `PanelUserPanelProvider`, `standard/profile` zeigt auf
  dieselbe Klasse). Enthält bereits ein dynamisch generiertes Formularfeld
  (`getNotificationComponent()`) — Vorbild für das neue Sprachfeld.
- `AdminPanelProvider`/`PanelUserPanelProvider` registrieren ihren Middleware-Stack jeweils über
  `$panel->middleware([...])`, u.a. `StartSession::class`, `AuthenticateSession::class` — die neue
  Locale-Middleware muss danach laufen (braucht Session + aufgelösten authentifizierten User).

**ADR-Bezug:**

- **ADR 0001** (Test-Layering): `LocaleDiscoveryService`-Test → `tests/Integration/Services/`
  (Vorbild `NotificationDiscoveryServiceTest.php`). Middleware-Test → `tests/Feature/...`, da ein
  echter HTTP-Request durch den Panel-Middleware-Stack laufen muss, um die Registrierung an der
  richtigen Stelle zu verifizieren — reines Aufrufen von `handle()` würde das Wiring nicht prüfen.
- **ADR 0002** (Suffix-Konvention): `LocaleDiscoveryService` (`*Service`, Pflicht). Für Middleware
  gibt es in ADR 0002 **keine** Suffix-Vorgabe (keine Zeile in der Tabelle) — Klassenname folgt
  Laravels eigener Konvention für Middleware (z.B. `EncryptCookies`, `TrimStrings` — kein Suffix):
  `App\Http\Middleware\SetUserLocale`.
- **ADR 0003** (SOLID/Patterns): Middleware bleibt ein dünner Adapter (ein `App::setLocale(...)`-Call,
  keine Business-Logik); Sprach-Discovery- und Label-Logik liegt im `LocaleDiscoveryService`, nicht
  in der Middleware oder der Filament-Page (Rule 1–3). Kein Caching-Decorator für den Service
  (anders als bei `NotificationDiscoveryService`) — YAGNI, da das Scannen von 2–3 Verzeichnissen
  trivial günstig ist, im Gegensatz zum Reflection-lastigen Notification-Scan.
- **ADR 0005** (PHPDoc): `LocaleDiscoveryService::list()`/`labels()` bekommen PHPDoc, da die
  Discovery-Logik (Verzeichnis-Scan, Ausschluss von `vendor`) nicht aus der Signatur ersichtlich ist.
- **ADR 0006** (Changelog): Eintrag unter `## [Unreleased]`, auf Englisch.

## Datenmodell

Neue Migration `database/migrations/2026_08_19_120000_add_locale_to_users_table.php`:

```php
Schema::table('users', static function (Blueprint $table) {
    $table->string('locale', 10)->nullable();
});
```

`locale` wird zu `User::$fillable` hinzugefügt (Filament-Formular-Mass-Assignment). `null` bedeutet
„Systemstandard" — kein zusätzlicher `$casts`-Eintrag nötig (String, kein Objekt-Cast).

## `User::preferredLocale()`

```php
public function preferredLocale(): string
{
    return $this->locale ?? config('app.locale');
}
```

## Sprach-Discovery

Neue `App\Services\LocaleDiscoveryService`:

```php
class LocaleDiscoveryService
{
    private const LABELS = [
        'de' => 'Deutsch',
        'en' => 'English',
    ];

    /**
     * App-eigene, verfügbare Sprachcodes: Top-Level-Verzeichnisse in lang/, ohne "vendor".
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
     * Anzeigename für einen Sprachcode; unbekannte Codes fallen auf den Großbuchstaben-Code zurück.
     */
    public function label(string $locale): string
    {
        return self::LABELS[$locale] ?? strtoupper($locale);
    }
}
```

Kein Facade (anders als `NotificationDiscovery`) — nur ein Aufrufort (`EditProfile`), daher
`app(LocaleDiscoveryService::class)` direkt, analog zu `app(UserMailConfigRepository::class)` in
derselben Datei.

## Middleware

Neue `App\Http\Middleware\SetUserLocale`:

```php
class SetUserLocale
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($user = $request->user()) {
            App::setLocale($user->preferredLocale());
        }

        return $next($request);
    }
}
```

Registrierung in `AdminPanelProvider::panel()` und `PanelUserPanelProvider::panel()`, jeweils im
bestehenden `->middleware([...])`-Array, **nach** `AuthenticateSession::class` (Reihenfolge wichtig:
`$request->user()` muss zu diesem Zeitpunkt bereits auflösbar sein):

```php
AuthenticateSession::class,
SetUserLocale::class,
ShareErrorsFromSession::class,
```

Gäste (nicht eingeloggt) behalten die globale `config('app.locale')` — kein Sonderfall nötig, da
`$request->user()` dann `null` ist und die Middleware einfach durchreicht.

## Profil-Formularfeld

Ergänzung in `App\Filament\Admin\Pages\Auth\EditProfile::form()`, neue private Methode
`getLocaleComponent()`:

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

Eingefügt in `form()`'s `->components([...])`-Array, z.B. direkt vor `getNotificationComponent()`.
Kein Eingriff in `handleRecordUpdate()` nötig — `locale` ist ein normales, jetzt fillable Feld und
wird über den bestehenden `parent::handleRecordUpdate($record, $data)`-Aufruf mitgespeichert.

## Übersetzungen

Neue Keys in `lang/de/filament.php` / `lang/en/filament.php` unter `admin.labels`:

```php
'locale' => 'Sprache',                          // en: 'Language'
'locale_system_default' => 'Systemstandard',    // en: 'System default'
```

## Tests

- `tests/Integration/Services/LocaleDiscoveryServiceTest.php` — `list()` enthält `de`/`en`, schließt
  `vendor` aus; `label()` liefert Klarnamen für bekannte Codes, Fallback (Großbuchstaben-Code) für
  unbekannte.
- `tests/Integration/Models/UserTest.php` (Ergänzung) — `preferredLocale()` liefert gespeicherten
  Wert, wenn gesetzt; fällt auf `config('app.locale')` zurück, wenn `null`.
- `tests/Feature/Http/Middleware/SetUserLocaleTest.php` — echter authentifizierter Request gegen
  eine Panel-Route mit `locale = 'en'` gesetzt → `App::getLocale()` ist `'en'` nach der Middleware;
  Gast-Request lässt `App::getLocale()` unverändert bei `config('app.locale')`.
- `tests/Integration/Filament/Admin/Pages/Auth/EditProfileLocaleTest.php` — Select-Feld existiert,
  Optionen enthalten `de`/`en`, Speichern persistiert `locale` auf dem User-Record.

## Out of Scope

- Keine Übersetzung/Lokalisierung von Datumsformaten (`Carbon::setLocale()`) — die App zeigt Daten
  bislang durchgängig im festen Format `d.m.Y, H:i` unabhängig von der Sprache; das bleibt so.
- Keine Sprachauswahl für nicht eingeloggte Besucher (Login-Seite, öffentliche Seiten) — nur
  authentifizierte Panel-Nutzer.
- Kein Caching-Decorator für `LocaleDiscoveryService` (siehe ADR-0003-Begründung oben).
