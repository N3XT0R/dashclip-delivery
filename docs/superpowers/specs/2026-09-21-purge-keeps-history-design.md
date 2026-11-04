# Purging deleted videos keeps their history (part 1 of 3)

Status: approved design, 2026-09-21. Follow-up to #375 (PR #377, merged).

## Context

The goal of the overall work is that videos leave the platform automatically once every channel
has had them, with a configurable number of distribution rounds. It is split into three parts:

1. **This spec:** removing a deleted video for good keeps its history.
2. Distribution rounds: a configurable number of rounds; in a new round only channels whose offer
   expired get the video again.
3. Mark a video as deleted automatically after its last round.

Part 3 will also delete videos that channels downloaded. Today `videos:purge-deleted` (#377)
calls `forceDelete()`, which cascades to assignments and downloads. That would erase the download
history of channel operators and submitters. Part 1 changes what "removing for good" means before
parts 2 and 3 exist.

**Deploy note:** `4.x-dev` already contains the `forceDelete` job. It must not reach production
before this part, otherwise the first run erases the history of every video deleted for longer
than the retention period, including videos that `DeleteVideosMissingFromStorage` deleted after
they had been downloaded.

## Decisions

- **Tombstone instead of hard delete.** A purged video keeps its database row (soft-deleted), its
  clips, assignments and downloads. Only its stored files are removed.
- **No new column.** The existing, so far unused `ProcessingStatusEnum::Deleted` marks "files
  removed". The meaning of the two markers:
  - `deleted_at` set: the video is deleted; its file is kept during the retention period.
  - additionally `processing_status = deleted`: the files are removed.
- **`Assignment::video()` stays as it is** (excludes deleted videos). Distribution, available
  offers, ZIP, CSV and the API rely on that. History views use new relations that include deleted
  videos and clips.

## 1. The purge job

`PurgeDeletedVideosUseCase` (command `videos:purge-deleted`, schedule unchanged):

- Candidates: `onlyTrashed()`, `deleted_at <= now - post_expiry_retention_weeks`, and
  `processing_status` is null or not `deleted`. `VideoRepository::lazyDeletedBefore()` gets the
  status condition, so every video is handled exactly once.
- Per video, in this order:
  1. delete the video file from its disk if it exists,
  2. delete the preview file of each of its clips (including soft-deleted clips) if it exists,
  3. set `processing_status = deleted` via `VideoRepository::updateProcessingStatus()`.
- The file removal lives in `VideoService::removeStoredFiles(Video): void`, which throws a
  `VideoFileRemovalException` (new, below `VideoException`, ADR 0004) when a file exists but
  cannot be deleted.
- On an exception: log it, count the video as failed, leave the status unchanged, continue. The
  next run retries it.
- No `forceDelete()` any more. `VideoObserver::forceDeleting()` stays for explicit hard deletes.
- `--dry-run` counts candidates without touching them.
- The command output reads "Removed the files of N deleted video(s), M failed." and "Would remove
  the files of N deleted video(s)." for the dry run; exit code as before.

## 2. Relations for history views

- `Assignment::videoWithTrashed(): BelongsTo` = `belongsTo(Video::class, 'video_id')->withTrashed()`.
- `Video::clipsWithTrashed(): HasMany` = `hasMany(Clip::class)->withTrashed()`.
- Views check `trashed()` on the loaded video to switch to the history presentation.

## 3. Views

### Download history (submitters, `DownloadHistory`)

- `DownloadRepository::forUser()` eager-loads `assignment.videoWithTrashed` instead of
  `assignment.video`.
- Title column reads `assignment.videoWithTrashed.original_name`; for a deleted video it has no
  link and the description "gelöscht" / "deleted".
- The "view video" action is hidden for deleted videos. This also fixes the current crash of
  `videoUrl()` when the video is soft-deleted.

### My offers (channel operators, `MyOffers`)

- `AssignmentTable::baseQuery()` eager-loads `videoWithTrashed.clipsWithTrashed.user`,
  `downloads`, `latestDownload`.
- `Assignment::scopeAvailable()` adds `whereHas('video')`, so offers of deleted videos never show
  under "Verfügbar" (and never get offered for download).
- Columns: title and uploaders read `videoWithTrashed` / `clipsWithTrashed`. For a deleted video
  the title gets the description "nicht mehr verfügbar" / "no longer available" and the preview
  column shows nothing. This fixes the current crash in `resolveUploaders()`.
- Row actions: `download_again`, `download` and `submit` are hidden when the video is deleted.
  `view_details` stays; its infolist uses `videoWithTrashed` / `clipsWithTrashed`, shows name and
  note, and hides the preview section for deleted videos (fixes the crash in
  `getDetailsInfolist()`).
- Bulk actions work on available offers only and are therefore unaffected.

### Administration

- `AssignmentResource` and `DownloadResource`: title columns read the `videoWithTrashed` relation
  and show a "gelöscht" description for deleted videos. No other admin changes.

### Unchanged

- The API: offers of deleted videos stay invisible, as today.
- Distribution, notifications, ZIP and CSV building keep using `video()`.

## 4. Privacy policy

`resources/views/datenschutz.blade.php`:

- The sentence "Gelöschte Videos und Vorschauen werden vollständig entfernt." becomes: "Videodateien
  und Vorschauen gelöschter Videos werden nach Ablauf einer Aufbewahrungsfrist entfernt. Angaben
  zur Verteilung (Titel, Kanal, Zeitpunkte von Angeboten und Downloads) bleiben zur
  Nachvollziehbarkeit erhalten."
- In the retention overview, "Videoinhalte: bis zur Entfernung" becomes "Videodateien: bis zur
  Löschung zuzüglich Aufbewahrungsfrist" and a new line "Angaben zur Verteilung gelöschter Videos:
  gemäß Audit-Erfordernissen" is added next to the existing offer and distribution line.
- The exact wording is shown to the user in the PR for approval.

## 5. Tests

Integration (`PurgeDeletedVideosUseCaseTest`, rewritten for the new behaviour):

- after the retention period: video file and clip previews are gone, the row still exists
  (soft-deleted), `processing_status` is `deleted`, assignments and downloads still exist;
- within the retention period and for videos that are not deleted: nothing happens;
- a second run does not touch an already purged video (candidate query excludes it);
- the configured retention period is respected;
- a file that cannot be deleted: the video stays with its old status and counts as failed, the
  others are still purged; the next run retries it;
- `--dry-run` changes nothing.

Command test: new output texts, schedule unchanged.

Feature (Livewire):

- Download history renders with a deleted video: name shown, no link, no "view video" action.
- My offers, tab "Heruntergeladen": renders with a deleted video, shows name and uploader, no
  download actions; the details modal opens without the preview section.
- My offers, tab "Verfügbar": an offer whose video is deleted does not show.
- Admin assignment and download lists render with a deleted video and show its name.

## Out of scope

- Parts 2 and 3.
- Restoring deleted videos.
- Anonymising metadata of purged videos.
