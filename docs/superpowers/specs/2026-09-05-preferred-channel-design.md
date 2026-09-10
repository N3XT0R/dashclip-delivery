# Design: Bevorzugter Kanal via `info.csv` (`preferred_channel`)

Ticket: #139

## Kontext

Einsender sollen in der `info.csv` optional eine Spalte `preferred_channel` befüllen können, damit ein
Video gezielt einem bestimmten Kanal zugewiesen wird — statt ausschließlich über den
Verteilungsalgorithmus (Round-Robin / Quota / Gewichtung). Die Wunschzuweisung darf Quoten,
Blocks und Team-Grenzen **nicht** aushebeln.

### Bestehender Ablauf (IST)

1. **Import** — `App\Services\InfoImporter` (`app/Services/InfoImporter.php`) liest die `info.csv`
   **positionsbasiert**: 7 Spalten `filename;start;end;note;bundle;role;submitted_by`, Trennzeichen
   `;`, die Header-Zeile wird nur zum Vorspulen gelesen und sonst ignoriert (`readHeader()`).
   `ROW_COLUMNS = 7`, `sanitizeRow()` padded jede Zeile mit `array_pad($row, 7, '')`. Jede Zeile
   erzeugt/aktualisiert einen `App\Models\Clip` (gehört zu genau einem `App\Models\Video`, Matching
   über `Video.original_name == basename(filename)`). Aufgerufen von
   `App\Services\CsvService::importCsvForDisk()` (Disk-Import, WebDAV/ZIP) und
   `App\Console\Commands\InfoImport` (CLI). Warnungen erhöhen `ClipImportResult::stats->warnings`;
   in `CsvService::importCsvForDisk()` verhindert `warnings > 0` das automatische Löschen der CSV
   (`deleteAfterSuccess`).
2. **Verteilung** — läuft **separat und später** (`weekly:run` → `App\Services\AssignmentDistributor`).
   - `collectPoolVideos()` (`app/Services/BatchService.php:102`) sammelt Videos **ohne** Assignment
     bzw. neuere als der letzte Assign-Batch, plus Requeue-Fälle.
   - `AssignmentDistributor::distribute()` partitioniert nach Team/Uploader, `expandBundles()` holt
     zusammengehörige Bundle-Videos dazu, `buildGroups()` bildet Gruppen (Bundle-Videos bleiben
     zusammen, sonst 1 Video = 1 Gruppe).
   - `ChannelService::prepareChannelsAndPool()` baut pro Uploader/Team den `ChannelPoolDto`
     (`channels`, gewichteter `rotationPool`, `quota` map `channel_id => Restkontingent der Woche`).
     Bei Team-Uploadern sind **nur** die dem Team zugewiesenen Kanäle (`channel_team`-Pivot) im Pool.
     Pausierte Kanäle (`is_video_reception_paused = true`) sind über `getActiveChannels()` **nie** im
     Pool.
   - `AssignmentDistributor::assignGroups()` iteriert die Gruppen, `ChannelService::pickTargetChannel()`
     wählt per gewichtetem Round-Robin über den `rotationPool` einen Kanal, der (a) genug Restquota
     für die ganze Gruppe hat, (b) für kein Video der Gruppe geblockt ist, (c) an den noch kein Video
     der Gruppe je zugewiesen wurde. `AssignmentService::assignGroupToChannel()` legt pro Video ein
     `Assignment` an (`AssignmentRepository::createAssignment()` → `status = QUEUED`) und
     dekrementiert die Quota im `AssignmentRun` (`app/ValueObjects/AssignmentRun.php`).
3. **Admin-Übersicht** — `App\Filament\Admin\Resources\Assignments\AssignmentResource` (Tabelle mit
   `channel.name`, `video.original_name`, `status`-Badge, `created_at`, Filtern). Navigation ist
   deaktiviert (`$shouldRegisterNavigation = false`), Zugriff über Relation-Manager /
   `BatchResource` / Direktlink.
4. **CSV-Export** — `App\Services\CsvService::buildInfoCsv()` erzeugt eine `info.csv` (Header +
   Zeilen) für ZIP-Downloads (`ZipService`), aktuell mit denselben Spalten wie der Import plus
   `hash`, `size_mb`.

### Betroffene Fixtures / Tests (IST)

- `tests/Fixtures/Videos/notizen.csv`, `tests/Fixtures/Inbox/Videos/notizen.csv` — 7-Spalten-CSV.
- `tests/Integration/Services/InfoImporterTest.php` — `writeCsv()` schreibt 7-Spalten-Zeilen
  (`array_pad($row, 7, '')`).
- `tests/Feature/Console/InfoImportTest.php` — CLI-Import.
- Distributor-Tests unter `tests/Integration/Services/` (Distributor / ChannelService).

### ADR-Bezug

- **ADR 0001** (Test-Layering, keine Mocks):
  - `PreferredChannelResolver` → `tests/Integration/Services/PreferredChannelResolverTest.php`
    (echte Eloquent-Modelle, DB).
  - Import-Auflösung → Erweiterung von `InfoImporterTest.php` (Integration).
  - End-to-end-Verteilung → Integrationstest auf Distributor-Ebene (echte Videos/Clips/Channels).
  - Admin-Spalte/Filter → Feature-Test (Filament-Livewire-Test) analog vorhandener
    Resource-Tests, falls vorhanden; sonst Integration auf Query-Ebene.
- **ADR 0002** (Suffix-Konvention): neuer Service `PreferredChannelResolver` — **`*Resolver`**
  ist kein in der ADR-0002-Tabelle gelisteter Pflicht-Suffix. Verwendeter Suffix ist `*Service`:
  Klassenname **`App\Services\PreferredChannelService`** (enthält wiederverwendbare Business-Logik
  = Definition von `*Service`). Repository-Methode neu in `App\Repository\ChannelRepository`.
- **ADR 0003** (SOLID/Patterns):
  - Auflösungs-/Kollaps-Logik gehört **nicht** in `InfoImporter` oder `AssignmentDistributor`,
    sondern in `PreferredChannelService` (SRP). `InfoImporter` ruft den Service nur an einer
    Stelle auf; der Distributor konsumiert eine Preload-Map.
  - Die gemeinsame Kanal-Akzeptanz-Prüfung (Quota/Block/bereits-zugewiesen) wird aus
    `ChannelService::pickTargetChannel()` in eine private Hilfsmethode extrahiert, die beide Pfade
    (Round-Robin **und** Wunschkanal) nutzen — keine Duplikation der Regeln.
  - Datenzugriff nur über Repositories (kein `Channel::query()` in Service/Importer neu eingeführt).
- **ADR 0004** (Exception-Hierarchien): Es werden **keine** neuen Exceptions geworfen. Ungültige
  Werte sind ein erwarteter Zustand (Fallback + Warnung), kein Fehler.
- **ADR 0005** (Method-Level-PHPDoc): `PreferredChannelService`-Methoden und die neue
  Repository-Methode bekommen PHPDoc (Auflösungsregeln, Konfliktverhalten und Rückgabe-Semantik
  sind nicht aus der Signatur ersichtlich).
- **ADR 0006** (Changelog): Eintrag unter `## [Unreleased]`, Englisch, Zeilen ≤ 120 Zeichen.
- **ADR 0007** (Conventional Commits): Commits referenzieren `#139`.

## Entscheidungen (mit Nutzer abgestimmt)

| Frage | Entscheidung |
|---|---|
| Referenzform in der CSV | Rein numerischer Wert → Kanal-**ID**; sonst → **Name**, case-insensitive + getrimmt. |
| Wunschkanal gültig, aber Wochenquota aufgebraucht | Gruppe wird in diesem Lauf **zurückgestellt** (kein Assignment), nächster `weekly:run` versucht den Wunschkanal erneut. Kein Quota-Override. |
| Pausierter Kanal (`is_video_reception_paused = true`) als `preferred_channel` | Wie **ungültig** behandeln → Standard-Algorithmus. |
| Wunschkanal gültig, aber nicht im Team-Kanal-Scope des Uploaders | Wie **ungültig** behandeln → Standard-(team-beschränkte)-Verteilung. |
| Ungültiger/unbekannter `preferred_channel` | **Warnung** über `$onWarning` + `incrementWarnings()` (konsistent mit „Kein Video gefunden"). Video läuft normal durch den Algorithmus. |

## Datenmodell

### Migration 1 — `clips`

`database/migrations/2026_09_05_120000_add_preferred_channel_to_clips_table.php`:

```php
Schema::table('clips', static function (Blueprint $table): void {
    // Rohwert aus der CSV, unabhängig von Gültigkeit — für Nachvollziehbarkeit/Debugging.
    $table->string('preferred_channel')->nullable()->after('submitted_by');
    // Beim Import aufgelöster Kanal; nur gesetzt, wenn existent UND nicht pausiert.
    $table->foreignId('preferred_channel_id')
        ->nullable()
        ->after('preferred_channel')
        ->constrained('channels')
        ->nullOnDelete();
});
```

`App\Models\Clip::$fillable` um `preferred_channel`, `preferred_channel_id` erweitern. Neue
Relation:

```php
public function preferredChannel(): BelongsTo
{
    return $this->belongsTo(Channel::class, 'preferred_channel_id');
}
```

### Migration 2 — `assignments`

`database/migrations/2026_09_05_120100_add_via_preferred_channel_to_assignments_table.php`:

```php
Schema::table('assignments', static function (Blueprint $table): void {
    $table->boolean('via_preferred_channel')->default(false)->after('note');
});
```

`App\Models\Assignment::$fillable` um `via_preferred_channel` erweitern, Cast `'boolean'`. Der
Wert wird zusätzlich in `getActivitylogOptions()->logOnly([...])` aufgenommen.

## Import (`InfoImporter` + `PreferredChannelService`)

### CSV-Format

Neue **8. Spalte** `preferred_channel`, ans Ende angehängt. Weiterhin positionsbasiert.

- `InfoImporter::ROW_COLUMNS` `7 → 8`.
- `sanitizeRow()`: `array_pad($row, 8, '')`, Rückgabe-Tupel + Destructuring in `processRow()` um
  `$preferredChannel` erweitern. Alte 7-Spalten-CSVs → 8. Wert `''` → keine Präferenz
  (**rückwärtskompatibel**).
- `CsvService::buildInfoCsv()`: Header-Array und Zeilen-Arrays um eine Spalte
  `preferred_channel` erweitern. Export-Wert = `clip->preferredChannel?->name ?? clip->preferred_channel`
  (aufgelöster Name bevorzugt, sonst Rohwert; `null` → leer). Der Video-ohne-Clips-Zweig schreibt
  `null`.

### Auflösung — `App\Services\PreferredChannelService`

```php
/**
 * Löst einen roh eingegebenen preferred_channel-Wert zu einer Kanal-ID auf.
 *
 * Regeln:
 *  - '' / null            → null (keine Präferenz)
 *  - rein numerisch       → Lookup per Kanal-ID
 *  - sonst                → Lookup per Name (case-insensitive, getrimmt)
 * Der Kanal muss existieren UND darf nicht pausiert sein
 * (is_video_reception_paused = false), sonst → null.
 *
 * @return int|null aufgelöste channel_id oder null, wenn nicht auflösbar
 */
public function resolveRawValue(string $raw): ?int
```

- „Rein numerisch" = `ctype_digit(trim($raw))`. Dann **nur** ID-Lookup, **kein** Namens-Fallback
  (deterministisch; ein Kanal, der wörtlich `"42"` heißt, ist per Name nicht über einen rein
  numerischen Wert adressierbar — akzeptierter Edge Case).
- Lookup-ID: `ChannelRepository::findById()`.
- Lookup-Name: neue Methode `ChannelRepository::findByNameInsensitive(string $name): ?Channel`
  (`whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->first()`). PHPDoc, da
  case-insensitivity nicht aus dem Namen ersichtlich.
- Pausiert-Prüfung: `$channel->is_video_reception_paused === false`.

### Einbindung in `processRow()`

Nach `applyDefaults()`, vor `findVideoOrWarn()`:

```php
$preferredChannelId = null;
if ($preferredChannel !== '') {
    $preferredChannelId = $preferredChannelService->resolveRawValue($preferredChannel);
    if ($preferredChannelId === null) {
        $result->incrementWarnings();
        $onWarning?->(
            "preferred_channel '{$preferredChannel}' nicht gefunden oder pausiert "
            . "für filename='{$baseName}'"
        );
    }
}
```

- `PreferredChannelService` wird per Konstruktor in `InfoImporter` injiziert (aktuell hat
  `InfoImporter` keinen Konstruktor — neuen hinzufügen; Auflösung über den Container, alle
  Aufrufer nutzen `app(InfoImporter::class)`).
- `createClip()`: zusätzliche Spalten `preferred_channel` (Rohwert, `'' → null`),
  `preferred_channel_id`.
- `updateClipIfDirty()`: gleiche „nur wenn nicht-leer und abweichend"-Semantik wie `note` —
  `preferred_channel` (Rohwert-String) und `preferred_channel_id` aktualisieren. Wichtig:
  Wenn ein Rohwert gesetzt ist, der aber zu `null` auflöst, wird `preferred_channel_id` auf `null`
  gesetzt (Wunsch wurde zurückgezogen bzw. ist ungültig geworden).
- Der `deprecated` `import(string $csvPath, ...)`-Pfad erhält dieselbe Behandlung (nutzt
  denselben `processRow()`).

## Verteilung (`AssignmentDistributor` / `ChannelService` / `PreferredChannelService`)

### Preload

Analog zu `preloadActiveBlocks()` / `preloadAssignedChannels()` in
`AssignmentDistributor::distribute()` (Schritt 5): eine Map
`preferredChannelIdByVideo: array<int, int>` — pro Video die **eindeutige** aufgelöste
`preferred_channel_id` aus seinen Clips.

`PreferredChannelService::preloadForVideos(Collection $videos): array`:

```php
/**
 * @param Collection<int,Video> $videos
 * @return array<int,int> video_id => preferred channel_id
 *   Enthält nur Videos, deren Clips genau EINEN nicht-null preferred_channel_id-Wert haben.
 *   Widersprüchliche Werte (mehrere verschiedene) → Video wird ausgelassen + Log::warning.
 */
public function preloadForVideos(Collection $videos): array
```

Query: `Clip::query()->whereIn('video_id', $ids)->whereNotNull('preferred_channel_id')
->get(['video_id','preferred_channel_id'])->groupBy('video_id')`. Pro Video:
`unique()` → 1 Wert = übernehmen; > 1 = auslassen + `Log::warning('conflicting preferred_channel
for video {id}', …)`.

### Gruppen-Wunschkanal

`PreferredChannelService::resolveForGroup(Collection $groupVideos, array $preferredChannelIdByVideo): ?int`:

```php
/**
 * Effektiver Wunschkanal einer (Bundle-)Gruppe.
 *  - kein Video der Gruppe hat eine Präferenz        → null
 *  - alle vorhandenen Präferenzen identisch          → dieser channel_id
 *  - unterschiedliche Präferenzen in der Gruppe      → null + Log::warning
 *
 * @param array<int,int> $preferredChannelIdByVideo
 */
public function resolveForGroup(Collection $groupVideos, array $preferredChannelIdByVideo): ?int
```

### `AssignmentRun` erweitern

Neues Feld `preferredChannelIdByVideo` (readonly array) im Konstruktor
(`app/Services/AssignmentDistributor.php` Schritt 6 + `app/ValueObjects/AssignmentRun.php`).
Neuer Zähler: Rückgabe von `assignGroups()` wird `[assigned, skipped, deferred]`.

### `ChannelService` — gemeinsame Akzeptanzprüfung

Extraktion aus `pickTargetChannel()`:

```php
/**
 * Prüft, ob ein konkreter Kanal die ganze Gruppe aufnehmen kann:
 * genug Restquota, nicht geblockt, an kein Video der Gruppe bereits vergeben.
 *
 * @param array<int,int> $quota
 * @param array<int,int> $blockedChannelIds
 * @param array<int,Collection<int,int>> $assignedChannelsByVideo
 */
public function channelAcceptsGroup(
    Channel $channel,
    Collection $group,
    array $quota,
    array $blockedChannelIds,
    array $assignedChannelsByVideo
): bool
```

`pickTargetChannel()` ruft künftig `channelAcceptsGroup()` in seiner Schleife auf (Verhalten
unverändert).

Neue Methode zum Finden des Wunschkanals im aktuellen Pool:

```php
/**
 * Liefert den Kanal aus dem Rotationspool, dessen Key $channelId entspricht,
 * oder null, wenn der Kanal nicht im (uploader-/team-spezifischen) Pool ist
 * (pausiert, nicht im Team-Scope, gelöscht).
 */
public function findPooledChannel(Collection $rotationPool, int $channelId): ?Channel
```

### `AssignmentDistributor::assignGroups()` — neuer Ablauf

Pro Gruppe (statt direkt `pickTargetChannel`):

```
preferredId = preferredChannelService->resolveForGroup($group, $run->preferredChannelIdByVideo)

if preferredId !== null:
    channel = channelService->findPooledChannel($run->channelPool->rotationPool, preferredId)
    if channel !== null:
        if channelService->channelAcceptsGroup(channel, $group, quota, blocked, assignedByVideo):
            assigned += assignmentService->assignGroupToChannel($group, $channel, $run, viaPreferred: true)
            if $run->quotasUsedUp(): break
            continue
        else:
            // gültig, aber Quota/Block/bereits-vergeben → diesen Lauf zurückstellen
            deferred += $group->count()
            continue
    // channel === null → nicht im Pool → Fallthrough zu Round-Robin

// unverändert:
channel = channelService->pickTargetChannel(...)
if !channel: skipped += $group->count(); continue
assigned += assignmentService->assignGroupToChannel($group, $channel, $run, viaPreferred: false)
if $run->quotasUsedUp(): break
```

Sonderfall „bereits an den Wunschkanal vergeben": `channelAcceptsGroup()` gibt `false` → Gruppe
würde jeden Lauf zurückgestellt. Damit ein Video nicht dauerhaft hängt, wird dieser Fall gesondert
behandelt: Wenn der einzige Grund für `false` die „bereits vergeben"-Regel ist (Video hat schon ein
Assignment für genau diesen Kanal), gilt der Wunsch als **erfüllt** → Fallthrough zum
Round-Robin für die restliche Verteilung, **nicht** zurückstellen. Umsetzung: `channelAcceptsGroup()`
so lassen; im Distributor zusätzlich prüfen
`assignmentRepo`-Preload (`assignedChannelsByVideo`) — wenn alle Videos der Gruppe bereits an
`preferredId` vergeben sind → Fallthrough statt defer.

> Hinweis: Reine Bundle-Teilkonflikte (ein Teil der Gruppe schon am Wunschkanal, ein Teil nicht)
> sind selten; sie werden wie „defer" behandelt und im nächsten Lauf erneut versucht. Kein
> Sondercode dafür (YAGNI).

### `AssignmentService` / `AssignmentRepository`

- `assignGroupToChannel(Collection $group, Channel $channel, AssignmentRun $run, bool $viaPreferred = false): int`
- `AssignmentRepository::createAssignment(Video $video, Channel $channel, Batch $batch, bool $viaPreferred = false): Assignment`
  → schreibt `via_preferred_channel => $viaPreferred` zusätzlich zu den bestehenden Feldern.

### Batch-Statistik

`AssignmentDistributor::distribute()` summiert `totalDeferred`. Die `batches`-Tabelle hat bereits
eine `json stats`-Spalte (`create_batches_table`), `BatchRepository::markAssignedBatchAsFinished()`
schreibt dort `['assigned' => …, 'skipped' => …]`.

- `BatchService::finishAssignBatch(Batch $batch, int $assigned, int $skipped, int $deferred = 0): bool`
- `BatchRepository::markAssignedBatchAsFinished(Batch $batch, int $assigned, int $skipped, int $deferred = 0): bool`
  → `stats` um `'deferred' => $deferred` erweitern.
- Kein Schema-Change (nutzt vorhandene `stats`-JSON-Spalte).
- Rückgabe von `AssignmentDistributor::distribute()` wird
  `['assigned' => …, 'skipped' => …, 'deferred' => …]`. Aufrufer prüfen (`WeeklyRun` u.a.) — nur
  additiv, bestehende Keys unverändert.

## Admin-Übersicht

`App\Filament\Admin\Resources\Assignments\AssignmentResource::table()`:

- Neue `IconColumn` (oder `TextColumn`-Badge) `via_preferred_channel`:
  ```php
  IconColumn::make('via_preferred_channel')
      ->label(__('filament.admin.labels.via_preferred_channel'))
      ->boolean()
      ->toggleable()
      ->sortable()
  ```
- Neuer `TernaryFilter::make('via_preferred_channel')`.
- Übersetzungen: `lang/de/filament.php` und `lang/en/filament.php` unter `admin.labels`:
  - de: `'via_preferred_channel' => 'Wunschkanal'`
  - en: `'via_preferred_channel' => 'Preferred channel'`
- `App\Filament\Admin\Resources\Assignments\Pages\ViewAssignment`: falls es eine Infolist/Detail
  gibt, dort denselben Boolean-Eintrag ergänzen (bei Umsetzung prüfen; optional).

Der Standard-Panel-Bereich (`MyOffers` etc.) wird **nicht** angefasst — das AK verlangt nur die
Admin-Übersicht.

## Tests

### `tests/Integration/Services/PreferredChannelServiceTest.php` (neu)

- `resolveRawValue('')` → `null`.
- numerischer Wert = existierende ID, Kanal aktiv → gibt ID zurück.
- numerischer Wert = existierende ID, Kanal pausiert → `null`.
- numerischer Wert = nicht existierende ID → `null`.
- Name exakt → ID; Name in abweichender Groß-/Kleinschreibung + Leerzeichen → ID.
- Name unbekannt → `null`.
- `preloadForVideos()`: Video mit 2 Clips gleicher `preferred_channel_id` → im Ergebnis;
  Video mit 2 Clips unterschiedlicher IDs → **nicht** im Ergebnis (+ kein Fehler).
- `resolveForGroup()`: Gruppe mit übereinstimmenden Präferenzen → ID; widersprüchliche → `null`;
  keine → `null`; Teilmenge hat Präferenz, Rest `null` → ID.

### `tests/Integration/Services/InfoImporterTest.php` (erweitern)

- `writeCsv()` auf 8 Spalten umstellen (`array_pad($row, 8, '')`), Header-Zeile ergänzen.
- Bestehende Tests bleiben grün (8. Wert leer).
- Neuer Test: Zeile mit `preferred_channel` = gültiger Kanalname → Clip hat `preferred_channel`
  (Rohwert) **und** `preferred_channel_id`; keine Warnung.
- Neuer Test: `preferred_channel` = gültige ID → dito.
- Neuer Test: `preferred_channel` = unbekannter Name → Clip hat Rohwert, `preferred_channel_id`
  `null`, `stats->warnings === 1`.
- Neuer Test: 7-Spalten-Zeile (ohne die neue Spalte) → unverändertes Verhalten,
  `preferred_channel_id` `null`.
- Update-Pfad: bestehender Clip, Re-Import mit neuem gültigen `preferred_channel` → aktualisiert.

### Distributor-Integrationstest (neu, `tests/Integration/Services/`)

- **Gültiger `preferred_channel` → Video landet beim gewünschten Kanal**, Assignment
  `via_preferred_channel = true`, andere Kanäle haben nichts bekommen.
- Fehlender Wert → normale Verteilung, `via_preferred_channel = false`.
- Ungültiger Wert (Kanal existiert nicht mehr / pausiert) → normale Verteilung.
- Wunschkanal pausiert / nicht im Team-Scope → normale Verteilung (kein Assignment am Wunschkanal).
- Wunschkanal-Quota = 0 → Video bleibt **unverteilt** in diesem Lauf (kein Assignment),
  `deferred`-Zähler erhöht; im Folgelauf mit freier Quota → landet am Wunschkanal.
- Wunschkanal für Video/Bundle **geblockt** (`ChannelVideoBlock`) → zurückgestellt.
- Bundle-Gruppe: alle Videos wünschen Kanal X → gesamte Gruppe an X.
- Bundle-Gruppe mit widersprüchlichen Wünschen → normale Verteilung.
- Quota bleibt gewahrt: Wunschzuweisung dekrementiert die Quota wie eine normale Zuweisung.

### `tests/Feature` — Admin

- Assignment mit `via_preferred_channel = true` → Spalte/Badge sichtbar; `TernaryFilter` filtert
  korrekt. (Filament-Livewire-Test analog vorhandener Resource-Tests; falls keine existieren,
  Query-Ebene im Integrationstest.)

### Fixtures

- `tests/Fixtures/Videos/notizen.csv` und `tests/Fixtures/Inbox/Videos/notizen.csv`: Header um
  `;preferred_channel(Kanalname oder -ID, optional)` erweitern; eine Beispielzeile mit gesetztem
  Wert, restliche Zeilen mit leerem 8. Feld.

## Changelog

`CHANGELOG.md` unter `## [Unreleased]` (Englisch, ≤ 120 Zeichen/Zeile), z. B.:

```
### Added
- `preferred_channel` column in `info.csv`: submitters can target a specific channel; the importer
  validates it and the distributor honours it without overriding quotas, blocks or team scope
  (#139).
- Admin assignment overview shows whether an assignment was made via `preferred_channel`.
```

## Nicht im Scope

- Kein UI zum Setzen des Wunschkanals (nur CSV).
- Keine Anzeige „ausstehender Wunschzuweisungen" (zurückgestellte Videos) im Admin — nur
  Logging/Batch-Zähler.
- Keine Änderung am Standard-Panel (`MyOffers`).
- Keine header-basierte CSV-Parser-Umstellung (bleibt positionsbasiert; `@todo`-Refactor in
  `InfoImport` bleibt bestehen).
- Kein Quota-Override, kein Ablauf-/Expiry-Sonderverhalten für Wunschzuweisungen.
