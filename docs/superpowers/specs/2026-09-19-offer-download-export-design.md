# Offer downloads through the export action

Date: 2026-09-19
Branch: `feature/offer-download-export`
Status: design approved section by section by the user on 2026-09-19; written specification awaiting review.

## Objective and boundaries

Move the ZIP download on the "Meine Angebote" page (`App\Filament\Standard\Pages\MyOffers`) from the
custom download dialog to the standard Filament export action
(https://filamentphp.com/docs/4.x/actions/export, running on Filament 5).

The result must stay functionally identical for the channel operator:

- one or several selected offers are delivered as **one ZIP** containing the video file(s) and an
  `info.csv` with exactly today's content: one row per clip of each packed video, one row for a packed
  video without clips, same columns, separator and BOM;
- videos that cannot be delivered are skipped instead of cancelling the archive, and are logged.

In scope: only "Meine Angebote" in the standard panel.

Out of scope: the deprecated public offer page `/offer/{batch}/{channel}` (still linked from offer
e-mails and the administration). It keeps its current flow unchanged: `ZipDownloader.js`, the
download dialog, the `zips.start`, `zips.progress` and `zips.download` routes,
`OfferDownloadPreparationService` and `BuildZipJob`. Replacing it is a separate follow-up project.

Decided with the user:

- The live progress dialog is dropped for "Meine Angebote". The user gets the Filament flow: a
  "export started" notification, then a database notification (bell and toast) with a
  "ZIP herunterladen" button once the archive is ready.
- Approach A: the real Filament export subsystem (`ExportBulkAction`, exporter class, `exports` table,
  `ExportCompletion` notification), with a custom job that builds the ZIP and a custom ZIP format
  whose notification button targets an application route. Rejected: a plain bulk action that only
  imitates the notification pattern (B), and a pure CSV/XLSX export next to a separate video ZIP (C).
- The direct per-video fallback links of the old dialog are not carried over.

## Filament constraints this design relies on

Verified in `vendor/filament/actions` (Filament 5):

- `ExportAction`/`ExportBulkAction` create an `Export` model, then dispatch
  `Bus::chain([Bus::batch([app($action->getJob(), [...])])->allowFailures(), ExportCompletion])`.
  The first job is replaceable with `->job(SomeJob::class)` and receives
  `export`, `query` (serialized Eloquent query), `columnMap`, `options`, `chunkSize`, `records`.
- `ExportCompletion` sends the notification. It adds the formats' download actions only when
  `getFailedRowsCount() < total_rows`; the colour follows `total_rows` versus `successful_rows`
  (success, warning, danger). Title and body come from the exporter.
- Formats implement `Filament\Actions\Exports\Enums\Contracts\ExportFormat`
  (`getDownloader()`, `getDownloadNotificationAction()`). The built-in download controller only
  resolves `ExportFormat::tryFrom('csv'|'xlsx')`, so a ZIP format needs its own route.
- Export files live on the export's disk in `Export::getFileDirectory()`
  (`filament_exports/{id}`); Filament never deletes them.
- Filament performs no per-record authorization for exports; the query must be scoped.
- Prerequisites: the `job_batches` table (`make:queue-batches-table`) and the Filament `exports` table
  (`vendor:publish --tag=filament-actions-migrations`). The `notifications` table already exists and
  the standard panel already enables database notifications.

## Components

### 1. Export actions on "Meine Angebote"

`ExportBulkAction` replaces the current `download_selected` bulk action in
`App\Filament\Standard\Pages\MyOffers\Table\BulkActions`, and `ExportAction` replaces the row
actions `download` (tab "available") and `download_again` (tab "downloaded") in
`App\Filament\Standard\Pages\MyOffers\Table\Actions`. A row action exports exactly its record
(`modifyQueryUsing(fn (Builder $query, Assignment $record) => $query->whereKey($record))`).
Shared configuration for all three:

- labels and visibility as today (bulk action and `download` on "available", `download_again` on
  "downloaded");
- `exporter(OfferExporter::class)`, `job(BuildOfferExportZipJob::class)`,
  `formats([OfferExportFormatEnum::Zip])`, `columnMapping(false)`, `maxRows(500)`;
- `options(['channel_id' => <current channel>])` hands the channel to the job;
- the table query is already scoped to the current channel, so selected records of other channels
  cannot be resolved; the job re-filters by channel and downloadability as defense in depth.

### 2. `App\Filament\Standard\Exports\OfferExporter`

- `getColumns()`: offer-level `ExportColumn`s (`id`, `video.original_name`). Filament requires at
  least one visible column; they are not used to build the archive. `info.csv` is produced by the
  existing `CsvService::buildInfoCsv()` unchanged, because it writes one row per clip, which
  per-record export columns cannot express.
- `getFormats()`: `[OfferExportFormatEnum::Zip]`.
- `getFileName()`: `videos_<channel>_<date>` (same pattern as today's archive name).
- `getCompletedNotificationTitle()` / `getCompletedNotificationBody()`: translated texts stating
  how many videos are ready and how many were skipped, for example
  "2 von 3 Videos sind bereit. 1 Video war nicht verfügbar und wurde übersprungen."
- `getJobQueue()`: not overridden; the export runs on the `default` queue that Horizon processes and
  `BuildZipJob` uses today.

### 3. `App\Jobs\BuildOfferExportZipJob`

Replaces Filament's `PrepareCsvExport` for this exporter (same constructor signature, `Batchable`):

1. Unserialize the query and load the selected offers with `video.clips`.
2. Re-apply the downloadability filter (`AssignmentRepository::fetchDownloadableForChannel`); offers
   that are no longer downloadable are skipped and logged with reason `offer_unavailable`.
3. Build the archive with `ZipService` (chunked remote transfer, per-video skipping and logging stay
   as they are). `ZipService` gets an entry point `buildArchive()` that writes the archive to a given
   absolute path, adds `info.csv` from `CsvService` for the packed offers and returns them; the old
   `build()` delegates to it.
4. Write `packed.json` (IDs of the packed offers) next to the ZIP in the export directory.
5. Update the export: `total_rows` = selected offers, `processed_rows` = `total_rows`,
   `successful_rows` = packed offers.
6. `failed()`: log every selected offer with reason `zip_failed` (not for `ZipEmptyException`,
   whose offers were already logged one by one), keeping today's behaviour.

Log entries use the default log channel and carry the export ID instead of the cache job ID.

### 4. `App\Enum\OfferExportFormatEnum`

Backed enum (`Zip = 'zip'`) implementing Filament's `ExportFormat` contract:

- `getDownloadNotificationAction()`: action "ZIP herunterladen" with a relative signed URL to
  `offers.exports.download` (parameters `export`, `authGuard`), marked as read on click;
- `getDownloader()`: required by the contract only; Filament's own download route never resolves
  this format, so it returns a downloader that delegates to the controller's delivery logic.

### 5. `App\Http\Controllers\OfferExportDownloadController`

Route `GET /offers/exports/{export}/download`, name `offers.exports.download`, signed:

- requires an authenticated user on the signed `authGuard`, otherwise 401;
- the user must be the export's creator, otherwise 403;
- the export must belong to `OfferExporter`, be completed and have its ZIP on disk, otherwise 404;
- marks exactly the offers listed in `packed.json` as downloaded through
  `AssignmentService::markDownloaded()` with IP and user agent (as today, on every fetch);
- streams the ZIP with its download name; HTTP range requests keep working; repeated fetches work
  while the export exists.

### 6. Cleanup

`CleanExpiredZipsCommand` additionally deletes `OfferExporter` exports older than 24 hours: the
export directory and the `exports` row. Afterwards an old notification button leads to 404; the
notification itself stays as history.

## Error handling

| Situation | Result |
|---|---|
| Video missing, unreadable, transfer incomplete, storage error | skipped, logged with reason, no `info.csv` row, not marked downloaded |
| Offer no longer downloadable when the job runs | skipped, logged `offer_unavailable` |
| Some videos skipped | warning notification with button; body names the skipped count |
| All videos skipped | no archive, danger notification without button |
| Job fails (for example disk full) | batch allows failures, completion sends danger notification without button; offers logged `zip_failed` |
| More than 500 offers selected | limited by `maxRows(500)` as today |

## User interface and texts

- New translations (German and English) in `lang/*/my_offers.php` for the action label, the started
  notification and the completed notification. No framework or internal terms in user-facing text.
- The notification arrives immediately through the existing broadcasting (Reverb), otherwise with
  the bell's 30-second polling.

## Removed for "Meine Angebote"

- the hidden `zip_form_anchor` view field and `filament.standard.components.zip-form-anchor`;
- `MyOffers::dispatchZipDownload()` and `App\Application\Offer\DispatchZipDownload`;
- the `zip-download` Livewire listener in `resources/js/app.js`;
- the route `zips.channel.start`, `ZipController::startForChannel()` and
  `LinkService::getZipSelectedUrlForChannel()`.

`public/build` is rebuilt and committed with the change.

## Testing

Following ADR 0001 (Feature tests for HTTP flows, Integration tests for framework boundaries):

- **Page** (`tests/Feature/Standard/Pages/MyOffersTest.php`): the export bulk action is visible only on
  "available", creates an export for the current user, and a selection including another channel's
  offer only exports own offers.
- **Job** (queue `sync`, test disk): one offer yields a ZIP with the video and an `info.csv`
  identical to today's; several offers yield one row per video; a missing video is skipped, counted
  (`successful_rows < total_rows`), has no row and is logged; all missing yields no archive and no
  download button; an expired offer is skipped and logged `offer_unavailable`; the completed database
  notification carries the signed button and the skipped count; `failed()` logs `zip_failed`.
- **Download**: the creator receives the ZIP and only packed offers become `picked_up`; another user
  gets 403; missing login or signature is rejected; a foreign exporter or unfinished export yields 404;
  repeated and range requests work.
- **Cleanup**: offer exports older than 24 hours are deleted with their files; newer ones and other
  exporters' exports stay.
- Existing tests of the public page flow (`zips.start`, progress, download, `DownloadReliabilityTest`
  for the batch route) keep passing; tests for removed parts are removed or rewritten.

## Changelog and rollout

- `CHANGELOG.md` under `[Unreleased]`, `Changed`: downloads on "Meine Angebote" are delivered through a
  notification with a download button instead of the progress dialog.
- The two migrations run automatically on deploy. Downloads prepared in the old dialog (24-hour cache)
  are not reachable after the switch; this affects at most downloads from the previous day.
