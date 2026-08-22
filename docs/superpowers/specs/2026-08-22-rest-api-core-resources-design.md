# Design: REST API — Sub-Projekt 2: Core API Resources (+ Swagger-Grundstein)

Ticket: #250 · Branch: `feature/rest-api-core-resources` (von `4.x-dev`)

## Kontext

Sub-Projekt 1 (OAuth2-Fundament, PR #316) ist gemerged: `api`-Guard (Passport), `routes/api.php`
mit `GET /api/user`-Sanity-Endpoint, Self-Service-Client/Token-Verwaltung in beiden Panels,
Scope-Grant-System (`n3xt0r/laravel-passport-authorization-core` 1.3.2 /
`n3xt0r/filament-passport-ui` 2.4.1). Die Scope-Taxonomie
(`PassportScopeResource`/`PassportScopeAction`) ist noch leer — ihre Befüllung wurde bewusst auf
dieses Sub-Projekt verschoben, weil sie die konkreten Ressourcen voraussetzt.

Dieses Sub-Projekt liefert die eigentlichen Domain-Endpoints (Videos, Channels, Offers, Teams)
inkl. Query-Parametern sowie — auf Wunsch vorgezogen aus Sub-Projekt 4 — die
l5-swagger-Integration mit vollständiger Dokumentation der neuen Endpoints.

### Entscheidungen aus dem Brainstorming

- **Nur Sub-Projekt 2** in dieser Branch (+ l5-swagger-Einbindung); Rate Limiting,
  ChannelWorkspace-Token-UI-Rest und Security-Härtung folgen separat.
- **„Offers" = `Assignment`-Model** (einem Channel angebotenes Video); „Comments" = die
  Assignment-`note`.
- **Datensicht wie Standard-Panel**: eigene Videos (über Clips/Team), Channels mit
  `channelUsers`-Zugriff, eigene Teams; keine Admin-Vollsicht per API.
- **`POST /videos` = echter Multipart-Datei-Upload** über dieselbe Ingest-Pipeline wie der
  Panel-Upload.
- **Response-Format: Laravel API Resources** (`data`/`meta`/`links`), kein JSON:API.
- **Ansatz A**: schlanke Controller + FormRequests + Eloquent API Resources +
  `spatie/laravel-query-builder`; Wiederverwendung vorhandener Policies, Model-Scopes und des
  Passport-Scope-Grant-Systems.

## Nicht in diesem Sub-Projekt

- Rate Limiting / `429` (Sub-Projekt 5)
- CORS-, HTTPS- und Audit-Logging-Härtung (Sub-Projekt 5)
- Restarbeiten ChannelWorkspace-Token-UI, u.a. Last-Used-Tracking (Sub-Projekt 3)
- Admin-API / Fremdeinsicht auf Ressourcen anderer User

## A) Architektur, Routing & Autorisierung

### Routing

Alle Endpoints unter `/api/v1/`, in `routes/api.php`, gebündelt unter `auth:api`:

```
GET    /api/v1/videos            GET/PATCH/DELETE /api/v1/videos/{video}
POST   /api/v1/videos            (Multipart-Upload)
GET    /api/v1/channels          GET/PATCH        /api/v1/channels/{channel}
GET    /api/v1/offers            GET              /api/v1/offers/{offer}
POST   /api/v1/offers            POST             /api/v1/offers/{offer}/comment
GET    /api/v1/teams             GET/DELETE       /api/v1/teams/{team}
POST   /api/v1/teams
```

Controller unter `app/Http/Controllers/Api/V1/` (`VideoController`, `ChannelController`,
`OfferController`, `TeamController`). „Offer" ist ausschließlich der API-Name; intern bleibt es
das `Assignment`-Model, `{offer}` bindet auf `Assignment`.

### Drei Autorisierungs-Ebenen

1. **Token-Scope** (darf dieses Token das überhaupt?): Passport-`scope:`-Middleware pro Route —
   `videos:read` auf GET, `videos:write` auf POST/PATCH, `videos:delete` auf DELETE; analog
   `channels:read|write`, `offers:read|write`, `teams:read|write|delete`. Die Taxonomie wird per
   Seeder in `PassportScopeResource`/`PassportScopeAction` angelegt (nur Aktionen, zu denen es
   Routen gibt) und erscheint damit automatisch in der Self-Service-Client-UI aus Sub-Projekt 1.
2. **Sichtbarkeit** (welche Datensätze sieht der User?): vorhandene Model-Scopes —
   `Video::hasUsersClips($user)`, `Channel::userHasAccess($user)`, `Team::isOwnTeam($user)`;
   Offers über die Kombination aus eigenen Channels und eigenen Videos.
3. **Aktions-Autorisierung** (darf der User das mit diesem Datensatz tun?): die vorhandenen
   Policies greifen unter dem `api`-Guard nicht — sie prüfen `$user->can(...)`, und Spatie löst
   Permissions über den Default-Guard der Anfrage auf (`api`, dafür existieren keine
   Permissions; empirisch verifiziert). Stattdessen prüft die API das Standard-Panel-Permission-
   Set explizit: Helper im `ApiController` auf Basis von
   `hasPermissionTo('<Permission>', GuardEnum::STANDARD->value)` plus explizite
   Ownership-Checks, wo Policies Ownership-Logik enthalten (z.B. `teams.owner_id` für
   `DELETE /teams/{team}`). Die API nutzt damit exakt dieselben Berechtigungen wie das
   Standard-Panel, ohne Policies oder Seeder anzufassen.

**404 statt 403 für fremde Datensätze** (kein Existenz-Leak): Route-Model-Binding läuft durch die
Sichtbarkeits-Scopes; was nicht sichtbar ist, existiert für die API nicht.

## B) Query-Parameter, Response-Format & Ressourcen im Detail

### Query-Parameter (`spatie/laravel-query-builder`)

- `?filter[...]=` — pro Ressource whitelisted: Videos: `processing_status`, `original_name`
  (partial), `created_at`-Range; Offers: `status`, `channel_id`; Channels:
  `is_video_reception_paused`; Teams: `name`.
- `?sort=` — whitelisted Spalten, z.B. `?sort=-created_at,original_name`; Default `-created_at`.
- `?page[number]=&page[size]=` — Default 25, Max 100, konfigurierbar in neuem `config/api.php`.

### Response-Format

Eloquent API Resources unter `app/Http/Resources/Api/V1/` (`VideoResource`, `ChannelResource`,
`OfferResource`, `TeamResource`). Einzelobjekt `{"data": {...}}`; Liste
`{"data": [...], "meta": {"pagination": {current_page, per_page, total, total_pages}},
"links": {first, last, prev, next}}`. Sensible Felder erscheinen nicht: kein `download_token`,
keine internen Pfade (`path`, `disk`), kein `hash`. Fehler einheitlich
`{"message": ..., "errors": {...}}` ohne Interna.

### Ressourcen

- **Videos:** `POST` = Multipart (`file`, `clip[start_sec]`, `clip[end_sec]`), spiegelt den
  Panel-Flow aus `CreateVideo`: Datei auf `videos`-Disk, Video-Datensatz (bytes/ext/path,
  `team_id` = Default-Team via `TeamRepository`), Clip via `ClipRepository`, dann
  `VideoQueuedForIngest`-Event → bestehende Ingest-Pipeline (Hash, Preview, Duplikat-Check).
  Antwort `201` inkl. `processing_status` (Client pollt Fortschritt via `GET /videos/{id}`).
  `PATCH` = nur Metadaten (`original_name`, Clip-Zeiten). `DELETE` via
  `VideoService::delete()` → `204`.
- **Channels:** `PATCH` beschränkt auf Betreiber-Felder (z.B. `is_video_reception_paused`);
  `weight`, `weekly_quota`, `approved_at` sind Admin-Felder und per API nicht schreibbar.
- **Offers:** `POST /offers` legt ein Assignment an (Video und Channel müssen dem User sichtbar
  sein). Da `assignments.batch_id` NOT NULL ist, werden API-erstellte Offers einem Batch vom Typ
  `api` zugeordnet (pro Request erzeugt, analog zu den Batch-Typen der Verteilungsläufe).
  `POST /offers/{offer}/comment` setzt die `note`. Statuswechsel (downloaded/expired) bleiben
  der bestehenden Pipeline vorbehalten — per API nicht schreibbar.
- **Teams:** `POST` (Name); `DELETE` nur für den Team-Owner via `TeamPolicy` → `204`.

## C) Swagger, Fehlerbehandlung & Teststrategie

### Swagger/OpenAPI (`darkaonline/l5-swagger`)

- OpenAPI-3.0 via PHP-8-Attribute (`#[OA\...]`) an den Controllern; wiederverwendbare
  Response-Schemas als dedizierte Klassen unter `app/OpenApi/Schemas/` (`Video`, `Channel`,
  `Offer`, `Team`, `PaginationMeta`, `ValidationError`).
- Zentrales `#[OA\Info]` + `#[OA\SecurityScheme]` (OAuth2/Bearer inkl. Scopes) in
  `app/OpenApi/OpenApiSpec.php`.
- UI unter `/api/documentation`; generierte Spec (`storage/api-docs/`) wird nicht committed.
  Ein Test ruft `l5-swagger:generate` auf — CI bricht, wenn Annotationen invalide sind.

### Fehlerbehandlung & Statuscodes (RFC 7231)

`bootstrap/app.php` → `withExceptions`: für `api/*` immer JSON, nie Redirects. Mapping:
unauthentifiziert → `401`; fehlender Token-Scope (`MissingScopeException`) → `403`;
unsichtbar/nicht existent → `404`; Validierung → `422` mit Feld-Errors; Unerwartetes → `500`
mit generischer Message (Details nur ins Log). `429` folgt in Sub-Projekt 5. Erfolgs-Codes:
`200` (GET/PATCH), `201` + `Location` (POST), `204` (DELETE). Response-Helper: kleine Basis im
`ApiController` (`created()`, `noContent()`), kein eigener Helper-Klassen-Zoo.

### Teststrategie

- Feature-Tests pro Endpoint unter `tests/Feature/Http/Api/V1/`: Happy Path, `401` ohne Token,
  `403` ohne passenden Scope, `404` für fremde Datensätze, `422`-Validierung,
  Pagination/Filter/Sort.
- Upload-Test mit `Storage::fake('videos')` + `Event::fake([VideoQueuedForIngest::class])`:
  Datei auf Disk, Video + Clip korrekt, Event dispatched; die Ingest-Pipeline selbst ist bereits
  getestet.
- Eigener Test für den Scope-Taxonomie-Seeder (vollständig, idempotent).
- Coverage-Schwelle bleibt `--min=90` (Workflow `core-branches`).
