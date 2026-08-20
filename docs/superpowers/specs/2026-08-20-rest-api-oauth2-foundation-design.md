# Design: REST API — Sub-Projekt 1: OAuth2-Fundament

## Kontext

Ticket #250 ("REST API Implementation") bündelt ein komplettes REST-API-Subsystem (OAuth2,
Ressourcen-Endpoints, Token-Management-UI, Swagger, Rate Limiting, Security-Härtung) — zu groß für
eine einzelne Spec. Es wird in fünf unabhängige Sub-Projekte zerlegt:

1. **OAuth2-Fundament** (dieses Dokument)
2. Core API Resources (Videos/Channels/Offers/Teams-Endpoints)
3. Channel-Workspace Token-Management-UI (größtenteils bereits durch Sub-Projekt 1 abgedeckt, siehe
   unten)
4. Swagger/OpenAPI-Dokumentation
5. Rate Limiting & Security-Härtung

Reihenfolge: 1 → 2 → 3/4 (3 kann parallel zu 2 laufen, sobald 1 steht) → 5.

### Bereits vorhandener Stand auf `feature/rest-api`

- Composer-Dependencies bereits vorhanden: `laravel/passport ^13.0`, `n3xt0r/filament-passport-ui
  ^2.3` (zieht `n3xt0r/laravel-passport-authorization-core` transitiv, aktuell `1.3.2`)
- Alle Passport-Kern-Migrationen (`oauth_*`) sowie die `passport_scope_*`-Migrationen aus
  `laravel-passport-authorization-core` sind bereits publiziert und migriert (siehe vorangegangene
  Fixes in dieser Session: UUID-FK-Typmismatch und `isMigrated()`-Robustheit gegen nicht erreichbare
  DB, beide bereits als `1.3.1`/`1.3.2` released)
- `config/passport-ui.php` bereits publiziert (nur Navigation-Icon/Gruppe konfiguriert)
- **Noch nicht vorhanden:** kein `routes/api.php`, `User`-Model implementiert weder
  `OAuthenticatable` noch `HasPassportScopeGrantsInterface`, kein `HasApiTokens`-Trait, kein
  `api`-Guard in `config/auth.php`, `FilamentPassportUiPlugin` ist in keinem Panel registriert

### Rechercheergebnis: vorhandene Paket-Infrastruktur, die bereits das Gewünschte leistet

- `LaravelPassportAuthorizationCoreServiceProvider`'s `PassportModelsBooter` ruft bei **jedem
  Boot** automatisch `Passport::useClientModel(...)` auf den erweiterten
  `N3XT0R\...\Models\Passport\Client` (mit `context_client_id`-Unterstützung) — kein manueller
  Aufruf in `AppServiceProvider` nötig.
- `config('passport-authorization-core.owner_model')` zeigt per Paket-Default bereits auf
  `App\Models\User`.
- Das Client-Erstellungsformular (`ClientWizardForm` → `ScopeCheckboxList`) nutzt im
  `user_permission`-Wizard-Schritt bereits `GrantedScopesByResourceProvider::get($owner,
  $contextClient)`, um nur die dem Owner bereits über `PassportScopeGrant` zugewiesenen Scopes
  anzuzeigen. Die gewünschte Einschränkung "User kann bei Client-Erstellung nur aus seinen
  zugewiesenen Scopes wählen" ist damit **funktional bereits vorhanden**, sobald `owner` korrekt
  auf den eingeloggten User zeigt — kein neuer Code nötig, nur Verdrahtung.
- Die offene Lücke: `ClientResource`/`TokenResource` haben **keine** Query-Einschränkung
  (`getEloquentQuery()`) und `OwnerSelect` listet frei **alle** User zur Auswahl — registriert man
  das Plugin unverändert im Standard-Panel, sieht jeder Channel-User die Clients/Tokens aller
  anderen User.

## Ziel / Abgrenzung

**Liefert:**

- Authentifizierte API-Anfragen funktionieren Ende-zu-Ende über Bearer-Token, nachgewiesen durch
  einen minimalen Sanity-Check-Endpoint (`GET /api/user`)
- Standard-Panel-User können sich selbst OAuth-Clients + Personal-Access-Tokens mit ihren
  zugewiesenen Scopes anlegen, verwalten und widerrufen — sehen dabei ausschließlich ihre eigenen
  Einträge, unabhängig von Rolle
- Admin-Panel behält die volle, uneingeschränkte Paket-Funktionalität (alle Clients/Tokens, freie
  Owner-Zuweisung, globale Scope-Taxonomie-Pflege)

**Liefert nicht:**

- Die eigentlichen Videos/Channels/Offers/Teams-REST-Endpoints (Sub-Projekt 2)
- Swagger/OpenAPI (Sub-Projekt 4)
- Rate Limiting (Sub-Projekt 5)
- Befüllung der Scope-Taxonomie (`PassportScopeResource`/`PassportScopeAction`) für die konkreten
  Domain-Ressourcen — das setzt voraus, dass die Ressourcen aus Sub-Projekt 2 feststehen
- Rollenbasierte Fremdeinsicht auf Clients/Tokens (im Brainstorming bewusst abgelehnt: "immer nur
  eigene", unabhängig von Rolle)

## A) App-seitige Passport-Kernkonfiguration (`dashclip-delivery`)

### User-Model

```php
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use N3XT0R\LaravelPassportAuthorizationCore\Models\Concerns\HasPassportScopeGrantsInterface;
use N3XT0R\LaravelPassportAuthorizationCore\Models\Traits\HasPassportScopeGrantsTrait;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication,
    HasAppAuthenticationRecovery, HasEmailAuthentication, MustVerifyEmail, HasTenants,
    HasDefaultTenant, HasLocalePreference, OAuthenticatable, HasPassportScopeGrantsInterface
{
    use HasFactory, Notifiable, HasRoles, LogsActivity, HasApiTokens, HasPassportScopeGrantsTrait;
    // ...
}
```

`HasPassportScopeGrantsTrait` liefert `passportScopeGrants(): MorphMany` (Vorbild: das paketeigene
`Client`-Model nutzt denselben Trait/dasselbe Interface für `contextScopeGrants()`) und macht den
User als `tokenable` für `PassportScopeGrant`-Einträge nutzbar — Voraussetzung dafür, dass
`GrantedScopesByResourceProvider` im Client-Formular greift.

### `config/auth.php`

```php
'guards' => [
    // ...
    'api' => [
        'driver' => 'passport',
        'provider' => 'users',
    ],
],
```

### `config/passport-authorization-core.php` (publizieren + Override)

Paket-Default für `oauth.allowed_grant_types` enthält `password` und `implicit` — beides von
Passport selbst als Legacy/nicht empfohlen markiert. Für dieses Projekt einschränken auf:

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

Begründung: Ticket-Erfolgskriterium "Security Review bestanden" — Legacy-Grants ohne konkreten
Bedarf offen zu lassen wäre unnötige Angriffsfläche.

### `AppServiceProvider::boot()`

```php
Passport::tokensExpireIn(now()->addDays(15));
Passport::refreshTokensExpireIn(now()->addDays(30));
Passport::personalAccessTokensExpireIn(now()->addMonths(6));
```

Werte sind konservative Standardwerte (Passport-Empfehlung), kein explizites Ticket-Requirement zu
konkreten Laufzeiten — bei Bedarf in Sub-Projekt 2/5 anpassbar.

### `routes/api.php` (neu) + `bootstrap/app.php`

```php
// routes/api.php
Route::middleware('auth:api')->get('/user', fn (Request $request) => $request->user());
```

```php
// bootstrap/app.php — withRouting() ergänzen um:
api: __DIR__.'/../routes/api.php',
```

Dient ausschließlich als End-to-End-Nachweis der Auth-Kette (Bearer-Token → `auth:api` →
authentifizierter User) für Sub-Projekt 1. Die tatsächlichen Domain-Endpoints kommen in
`routes/api.php` erst mit Sub-Projekt 2 dazu.

## B) Paket-Erweiterung: Self-Service-Modus (`filament-passport-ui`, `~/PhpstormProjects/filament-passport-ui`)

Eigenes Repo, eigener Branch (`feature/self-service-mode`), gleicher Workflow wie die bisherigen
Fixes in dieser Session (Branch → Implementierung → Tests → CHANGELOG → Commit → Push/Tag durch den
User).

**Zusätzlicher Bugfix im selben Zug:** `FilamentPassportUiPlugin::registerResources()` liest
`config('filament-passport-ui.enable_scopes_management', true)`, das Paket publiziert seine Config
aber unter dem Namespace `passport-ui` (`hasConfigFile('passport-ui')`) und definiert den Schlüssel
dort auch nicht. Die Option ist dadurch aktuell nicht konfigurierbar (immer `true`). Fix: Lesezugriff
auf `config('passport-ui.enable_scopes_management', true)` korrigieren und den Schlüssel in der
paketeigenen `config/passport-ui.php` mit Default `true` ergänzen.

- Neue Plugin-Factory-Methode `FilamentPassportUiPlugin::selfService(): static`, die einen
  internen Zustand setzt (statt globalem Config-Flag) — explizit am Registrierungsort im
  Panel-Provider sichtbar, kein impliziter globaler Zustand, der zwischen Panel-Registrierungen
  kollidieren könnte.
- `ClientResource`/`TokenResource`: `getEloquentQuery()`-Override, aktiv wenn Self-Service-Modus
  gesetzt ist → `ClientResource` schränkt auf `owner_id`/`owner_type` des aktuell authentifizierten
  Users ein; `TokenResource` nutzt die direkte `user_id`-Spalte auf `oauth_access_tokens`
  (`Laravel\Passport\Token`), keine Notwendigkeit über die Client-Relation zu gehen.
- `OwnerSelect`: im Self-Service-Modus `->hidden()` und State serverseitig fix auf den
  eingeloggten User gesetzt (nicht clientseitig vertrauenswürdig überschreibbar) — z. B. über einen
  `mutateFormDataBeforeCreate`-Hook in `CreateClient`, der `owner_id`/`owner_type` unabhängig von
  eingereichten Formulardaten setzt.
- Wie das Plugin dem jeweiligen Panel mitteilt, ob es sich im Self-Service-Modus befindet
  (Panel-Property, Container-Binding pro Panel-ID o. ä.) ist Implementierungsdetail des
  Paket-Fixes — muss beim Umsetzen so gewählt werden, dass Admin- und Standard-Panel im selben
  Request-Prozess (z. B. Tests, die beide Panels laden) keinen gemeinsamen globalen Zustand teilen.

## C) Panel-Registrierung (`dashclip-delivery`)

```php
// AdminPanelProvider::panel()
->plugin(FilamentPassportUiPlugin::make())

// PanelUserPanelProvider::panel()
->plugin(FilamentPassportUiPlugin::make()->selfService())
```

Admin-Panel bleibt unverändert (volle Paket-Funktionalität: alle Clients/Tokens, freie
Owner-Zuweisung, `PassportScopeResourceResource`/`PassportScopeActionsResource` zur
Taxonomie-Pflege). Standard-Panel bekommt ausschließlich `ClientResource`/`TokenResource` im
Self-Service-Modus — `enable_scopes_management` bleibt auf dem (nach Bugfix tatsächlich wirksamen)
Default `true`, da die
Taxonomie-Resourcen ohnehin nur global editierbar sein sollen und dort keine Owner-Scoping-Frage
existiert; ob sie im Standard-Panel überhaupt sichtbar sein sollen, wird über die bestehende
Filament-Rollen-/Rechte-Vergabe geregelt (kein neues Konzept nötig — bestätigt im Brainstorming:
"zwecks Admin-Funktionalität ist das eh über die Rollen geregelt").

## D) Fehlerbehandlung

- Kein/ungültiges Bearer-Token gegen `auth:api`-Route → Passport-Standardverhalten: `401 Json
  {"message": "Unauthenticated."}`
- Widerrufener Client → Tokenausstellung schlägt mit Passport-Standardfehler fehl, kein
  App-spezifischer Code nötig
- Self-Service-User ruft die Detail-/Edit-Seite eines fremden Clients direkt per URL auf →
  `getEloquentQuery()`-Scoping lässt den Record nicht finden → Filament-Standardverhalten (404)

## Tests

**`dashclip-delivery`:**

- `tests/Feature/Http/Api/AuthenticatedUserEndpointTest.php` — gültiges Bearer-Token → `200` +
  User-JSON; fehlendes/ungültiges Token → `401`
- `tests/Integration/Models/UserTest.php` (Ergänzung) — `User` implementiert `OAuthenticatable`
  und `HasPassportScopeGrantsInterface`, `passportScopeGrants()` liefert eine `MorphMany`-Relation
- `tests/Feature/Filament/Standard/...` (neu) — ein Standard-User sieht in `ClientResource` und
  `TokenResource` ausschließlich seine eigenen Einträge, nicht die eines zweiten angelegten Users
  (End-to-End über die tatsächliche Panel-Registrierung, nicht isoliert auf Paket-Ebene)

**`filament-passport-ui`:**

- Feature-Test: Self-Service-Modus aktiv → `ClientResource`-Query liefert nur Records mit
  passendem Owner
- Integration-Test: `OwnerSelect` im Self-Service-Modus ist `hidden`, Formular-Submit mit
  manipuliertem `owner`-Feld ändert den tatsächlich gespeicherten Owner nicht
- Test für den `enable_scopes_management`-Namespace-Fix: `config(['passport-ui.enable_scopes_management' => false])` → `PassportScopeResourceResource`/`PassportScopeActionsResource` werden nicht
  registriert
- Bestehende Admin-Pfad-Tests bleiben unverändert grün (Regressionsnachweis, dass der
  Standard-Modus unangetastet bleibt)

## ADR-Bezug

- **ADR 0001** (Test-Layering): Der `AuthenticatedUserEndpointTest` gehört nach `tests/Feature/`,
  da ein echter HTTP-Request durch den `auth:api`-Middleware-Stack läuft (kein reiner
  Unit-/Integration-Test möglich, ohne das eigentliche Wiring zu verifizieren).
- **ADR 0002** (Suffix-Konvention): Keine neuen Middleware-Klassen in diesem Sub-Projekt — `auth:api`
  ist Passports eigene Middleware.
- **ADR 0006** (Changelog): Einträge in **beiden** Repos unter `## [Unreleased]`, auf Englisch.

## Out of Scope

- Videos/Channels/Offers/Teams-REST-Endpoints (Sub-Projekt 2)
- Swagger/OpenAPI (Sub-Projekt 4)
- Rate Limiting (Sub-Projekt 5)
- Scope-Taxonomie-Befüllung für konkrete Domain-Ressourcen (folgt mit Sub-Projekt 2)
- Rollenbasierte Fremdeinsicht auf Clients/Tokens anderer User (bewusst abgelehnt)
