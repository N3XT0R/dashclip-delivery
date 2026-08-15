# Design: Download-Verlauf-Seite (Standard-Panel)

## Kontext

Video-Ersteller (Clip-Uploader) sollen im Standard-Panel, innerhalb der bestehenden Navigationsgruppe
„Meine Inhalte" (`nav.media`), eine neue Seite erhalten, die ihnen zeigt, welche ihrer Videos von welchem
Kanal heruntergeladen wurden — chronologisch absteigend nach Download-Zeitpunkt.

Bestehende Referenzen im Code:

- `App\Filament\Standard\Resources\VideoResource` — liegt bereits in der Gruppe „Meine Inhalte" und
  scoped seine Query auf Videos, die Clips des eingeloggten Users enthalten
  (`whereHas('clips', fn ($q) => $q->where('clips.user_id', Filament::auth()->id()))`).
- `App\Filament\Admin\Resources\Downloads\DownloadResource` — listet global alle `Download`-Datensätze
  (Video, Kanal, Zeitpunkt) für Admins; dient als fachliches Vorbild für Spaltenwahl.
- `App\Filament\Standard\Pages\MyOffers` — Beispiel für eine Custom-Page mit `HasTable` +
  `InteractsWithTable`, die ihre Tabelle über `content(Schema)` mit `EmbeddedTable::make()` einbettet
  (kein eigenes Blade-Template nötig).
- `App\Filament\Standard\Pages\ChannelApplication` — Beispiel für eine einfache, ungetabbte
  Standard-Panel-Page mit eigenem, auf den aktuellen User gescopten Table-Query.
- `App\Repository\DownloadRepository` / `App\Repository\VideoRepository` — bestehende Repository-Klassen,
  die Query-Konstruktion für `Download`/`Video` kapseln und von Filament-Resources/-Pages aufgerufen werden.
- `App\Models\Assignment::scopeHasUsersClips(Builder $query, User $user)` — bestehender, wiederverwendbarer
  Scope: „Assignments, deren Video Clips des angegebenen Users enthält".

Datenmodell: `Download belongsTo Assignment`, `Assignment belongsTo Video` + `belongsTo Channel`,
`Video hasMany Clip` (`Clip.user_id` verweist auf den Ersteller). Ein Download-Datensatz repräsentiert
ein einzelnes Download-Ereignis (`downloaded_at`); ein Assignment kann theoretisch mehrfach heruntergeladen
werden (`Assignment hasMany Download`).

**ADR-Bezug:** [ADR 0003](../../adr/0003-solid-compliance-and-established-design-patterns.md), Rule 1
(„Filament resources do not own business workflows") und Rule 2 („Repositories encapsulate data access")
verlangen, dass die Query-Konstruktion nicht direkt in der Page landet, sondern in einer
Repository-Methode gekapselt wird. Die Page delegiert an das Repository.

## Ziel

Eine neue, rein lesende Filament-Page im Standard-Panel, die pro Download-Ereignis eine Zeile zeigt:
Video (verlinkt zur zugehörigen Video-Detailseite) und der Name des herunterladenden Kanals, sortiert
chronologisch absteigend nach Download-Zeitpunkt.

## Architektur

Neue Klasse `App\Filament\Standard\Pages\DownloadHistory` (Auto-Discovery via
`discoverPages(in: app_path('Filament/Standard/Pages'), ...)`, keine manuelle Panel-Registrierung nötig):

- `implements HasTable`, `use InteractsWithTable`
- Kein Formular, keine Tabs, kein Modal — daher **kein** eigenes Blade-Template. Die Tabelle wird über
  `content(Schema $schema): Schema` mit `EmbeddedTable::make()` eingebettet (analog `MyOffers`).
- Kein `canAccess()`-Override — jeder Standard-Panel-User sieht seine eigene (ggf. leere) Liste, analog
  `VideoResource`.

### Navigation

- `navigationGroup` = `nav.media` ("Meine Inhalte")
- `navigationIcon` = `Heroicon::OutlinedClock`
- `navigationLabel` / `getTitle()` = `download_history.navigation_label` / `download_history.title`
  ("Download-Verlauf")
- `navigationSort = 1`: `VideoResource` setzt keinen `navigationSort` (Filament-Default für
  `NavigationItem::getSort()` ist `-1`, siehe `vendor/filament/filament/src/Navigation/NavigationItem.php`).
  Mit einem expliziten Wert `> -1` erscheint „Download-Verlauf" garantiert **unterhalb** von „Videos",
  unabhängig von der Resource/Page-Discovery-Reihenfolge — ohne `VideoResource` selbst anzufassen.

## Datenquelle & Scoping

Neue Methode `DownloadRepository::forUser(User $user): Builder`, die den bestehenden
`Assignment::scopeHasUsersClips()`-Scope wiederverwendet, statt die Verschachtelung erneut von Hand
nachzubauen:

```php
// App\Repository\DownloadRepository
public function forUser(User $user): Builder
{
    return Download::query()
        ->whereHas('assignment', fn (Builder $q) => $q->hasUsersClips($user))
        ->with(['assignment.video', 'assignment.channel']);
}
```

Die Page ruft ausschließlich das Repository auf und übernimmt keine eigene Query-Konstruktion:

```php
// App\Filament\Standard\Pages\DownloadHistory::table()
$table
    ->query(fn () => app(DownloadRepository::class)->forUser(auth()->user()))
    ->defaultSort('downloaded_at', 'desc')
    ->columns([...]);
```

## Tabelle

| Spalte | Quelle / Verhalten |
|---|---|
| Video | `assignment.video.original_name`; `TextColumn` mit `->url(fn ($record) => VideoResource::getUrl('view', ['record' => $record->assignment->video]))` — Klick auf den Namen führt direkt zum Video-Eintrag |
| Kanal | `assignment.channel.name` — nur der Name, keine weiteren Kanal-Infos, kein eigener Link |
| Heruntergeladen am | `downloaded_at`, `->dateTime('d.m.Y, H:i')->since()->dateTimeTooltip()`, `->sortable()` |

**Record-Action** „Video ansehen": Button mit Eye-Icon, `->url(...)` zur selben Ziel-URL wie die
Video-Spalte (redundanter, expliziter Action-Button zusätzlich zum verlinkten Namen, wie vom
Auftraggeber gefordert).

Keine Filter, keine Such-Funktion (bewusste Entscheidung — schlanke, rein chronologische Liste).

**Empty State:** Heading + Beschreibung ("Noch keine Downloads" / erläuternder Text), analog zum
Empty-State-Pattern in `VideoResource`.

## Übersetzungen

Neue, eigenständige Sprachdateien (nicht in `filament.php` verschachtelt), analog zu `my_offers.php`:

- `lang/de/download_history.php`
- `lang/en/download_history.php`

Enthalten: `title`, `navigation_label`, `table.columns.{video,channel,downloaded_at}`,
`table.actions.view_video`, `empty_state.{heading,description}`.

## Tests

`tests/Feature/Filament/Standard/Pages/DownloadHistoryTest.php` (analog `ChannelApplicationTest.php`):

1. Seite lädt erfolgreich für einen authentifizierten Standard-Panel-User.
2. Nur Downloads von Videos, die Clips des eingeloggten Users enthalten, werden angezeigt — Downloads
   fremder Videos/User werden ausgeblendet.
3. Die Tabelle ist standardmäßig chronologisch absteigend nach `downloaded_at` sortiert.
4. Der Video-Link (Spalte) sowie die „Video ansehen"-Action zeigen auf die korrekte
   `VideoResource`-`ViewVideo`-URL des jeweiligen Videos.

Zusätzlich ein fokussierter Unit-/Repository-Test für `DownloadRepository::forUser()` (Query-Scoping
isoliert von der Filament-Page geprüft), passend zur in [ADR 0001](../../adr/0001-test-architecture-and-layering.md)
beschriebenen Schichtung von Tests.

## ADR-Konformität

- **ADR 0001** (Test-Architektur/Layering): Repository-Logik wird separat vom Filament-Page-Test
  abgedeckt, nicht nur indirekt über die UI-Tabelle.
- **ADR 0002** (Klassennamen-Suffixe): `DownloadHistory` folgt Filaments eigener Page-Namenskonvention
  (wie `MyOffers`, `ChannelApplication`, `Dashboard`) — kein künstliches Suffix nötig.
- **ADR 0003** (SOLID/Design Patterns): Query-Konstruktion liegt im `DownloadRepository`, nicht in der
  Page (Rule 1 + Rule 2); die Page delegiert nur.
- **ADR 0005** (Method-Level PHPDoc): `DownloadRepository::forUser()` erhält einen PHPDoc-Block
  (Zweck, `$user`-Bedeutung, Rückgabewert), FQCN werden per `use`-Import referenziert, nicht inline.

## Out of Scope

- Filter/Suche in der Tabelle
- Bearbeiten/Löschen von Download-Einträgen
- Anzeige weiterer Kanal-Details außer dem Namen
- Änderungen an `VideoResource` selbst
