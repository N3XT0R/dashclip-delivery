# Purge Keeps History Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** `videos:purge-deleted` removes only the stored files of deleted videos and keeps their database rows, clips, assignments and downloads, and every history view renders deleted videos without crashing.

**Architecture:** The purge job stops calling `forceDelete()`. `VideoService::removeStoredFiles()` deletes the video file and clip previews, then the job sets `processing_status = deleted`, which also excludes the video from later runs. History views read new relations that include soft-deleted videos and clips (`Assignment::videoWithTrashed()`, `Video::clipsWithTrashed()`); `Assignment::video()` keeps excluding them so distribution, ZIP, CSV and the API stay unchanged.

**Tech Stack:** Laravel 12, Filament 5 (Livewire), PHPUnit 12, SQLite in tests.

**Spec:** `docs/superpowers/specs/2026-09-21-purge-keeps-history-design.md`

## Global Constraints

- Branch: `feature/375-purge-keeps-history` (already created off `4.x-dev`, spec committed).
- Run tests only in the container: `docker exec -e XDEBUG_MODE=off dashclip-delivery-sharing-1 php artisan test --no-coverage <path>`; several paths need one call each; `--parallel` must come before `--no-coverage`.
- ADR 0002: suffixes `*Service`, `*Exception`, `*UseCase`, `*Command`. ADR 0004: new exceptions below `App\Exceptions\Video\VideoException`. ADR 0005: method PHPDoc with imported types.
- No em-dashes anywhere (code, comments, texts, commits). No framework names or internal structure ("Panel") in user-facing texts.
- Changelog entries go under `## [Unreleased]`, lines at most 120 characters.
- Commits: Conventional Commits, reference `#375`, end with `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`. Never `git add -A`.
- PR #363 (offer export) is still open and touches `MyOffers/Table/Actions.php`, `AssignmentTable.php` and `Columns.php`; expect merge conflicts there and resolve them by keeping both changes.

---

### Task 1: File removal in `VideoService`

**Files:**
- Create: `app/Exceptions/Video/VideoFileRemovalException.php`
- Modify: `app/Services/VideoService.php` (add method at the end of the class)
- Modify: `app/Models/Video.php` (add `clipsWithTrashed()` next to `clips()`)
- Test: `tests/Integration/Services/VideoServiceRemoveStoredFilesTest.php`

**Interfaces:**
- Produces: `App\Services\VideoService::removeStoredFiles(Video $video): void`, throws `App\Exceptions\Video\VideoFileRemovalException`
- Produces: `App\Models\Video::clipsWithTrashed(): HasMany` (clips including soft-deleted ones)

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Exceptions\Video\VideoFileRemovalException;
use App\Models\Clip;
use App\Models\Video;
use App\Services\VideoService;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

final class VideoServiceRemoveStoredFilesTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('previews');
    }

    public function testRemovesTheVideoFileAndThePreviewsOfDeletedClips(): void
    {
        $video = Video::factory()->create(['disk' => 'local']);
        Storage::disk('local')->put($video->path, 'video-bytes');
        $clip = Clip::factory()->forVideo($video)->create([
            'preview_disk' => 'previews',
            'preview_path' => 'previews/clip.mp4',
        ]);
        Storage::disk('previews')->put('previews/clip.mp4', 'preview-bytes');
        $video->delete(); // soft-deletes the clips as well (VideoObserver::deleting)

        app(VideoService::class)->removeStoredFiles($video);

        Storage::disk('local')->assertMissing($video->path);
        Storage::disk('previews')->assertMissing('previews/clip.mp4');
        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
        $this->assertSoftDeleted('clips', ['id' => $clip->getKey()]);
    }

    public function testMissingFilesAreNotAnError(): void
    {
        $video = Video::factory()->create(['disk' => 'local']);
        Clip::factory()->forVideo($video)->create(['preview_disk' => null, 'preview_path' => null]);

        app(VideoService::class)->removeStoredFiles($video);

        Storage::disk('local')->assertMissing($video->path);
    }

    public function testAnUnusableDiskIsReportedAsVideoFileRemovalException(): void
    {
        $video = Video::factory()->create(['disk' => 'broken']);

        $this->expectException(VideoFileRemovalException::class);

        app(VideoService::class)->removeStoredFiles($video);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker exec -e XDEBUG_MODE=off dashclip-delivery-sharing-1 php artisan test --no-coverage tests/Integration/Services/VideoServiceRemoveStoredFilesTest.php`
Expected: FAIL, `VideoFileRemovalException` / `removeStoredFiles` not defined.

- [ ] **Step 3: Implement**

`app/Exceptions/Video/VideoFileRemovalException.php`:

```php
<?php

declare(strict_types=1);

namespace App\Exceptions\Video;

/**
 * Thrown when a stored file of a video exists but cannot be removed.
 */
class VideoFileRemovalException extends VideoException
{
}
```

`app/Models/Video.php`, below the existing `clips()` relation (add `use Illuminate\Database\Eloquent\Relations\HasMany;` if missing):

```php
    /**
     * Clips including the ones soft-deleted together with the video, for history views.
     * @return HasMany<Clip>
     */
    public function clipsWithTrashed(): HasMany
    {
        return $this->hasMany(Clip::class)->withTrashed();
    }
```

`app/Services/VideoService.php`, new method (add `use App\Exceptions\Video\VideoFileRemovalException;` and `use Throwable;`):

```php
    /**
     * Remove the stored video file and the preview files of all clips; the database rows stay.
     * @param Video $video
     * @return void
     * @throws VideoFileRemovalException when a file exists but cannot be removed
     */
    public function removeStoredFiles(Video $video): void
    {
        try {
            $disk = $video->getDisk();
            if ($disk->exists($video->path) && !$disk->delete($video->path)) {
                throw new VideoFileRemovalException('The video file could not be removed.');
            }

            foreach ($video->clipsWithTrashed()->get() as $clip) {
                $previewPath = $clip->getAttribute('preview_path');
                if (!$previewPath || !$clip->getAttribute('preview_disk')) {
                    continue;
                }

                $previewDisk = $clip->getDisk();
                if ($previewDisk->exists($previewPath) && !$previewDisk->delete($previewPath)) {
                    throw new VideoFileRemovalException('A clip preview could not be removed.');
                }
            }
        } catch (VideoFileRemovalException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new VideoFileRemovalException($exception->getMessage(), previous: $exception);
        }
    }
```

- [ ] **Step 4: Run the test to verify it passes**

Run: same command as Step 2. Expected: 3 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Exceptions/Video/VideoFileRemovalException.php app/Services/VideoService.php app/Models/Video.php tests/Integration/Services/VideoServiceRemoveStoredFilesTest.php
git commit -m "feat(videos): remove the stored files of a video without deleting its row (#375)

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 2: Purge job keeps the rows

**Files:**
- Modify: `app/Repository/VideoRepository.php` (`lazyDeletedBefore()`)
- Modify: `app/Application/Cleanup/PurgeDeletedVideosUseCase.php`
- Modify: `app/Console/Commands/PurgeDeletedVideosCommand.php`
- Test: `tests/Integration/Application/Cleanup/PurgeDeletedVideosUseCaseTest.php` (rewrite)
- Test: `tests/Integration/Commands/PurgeDeletedVideosCommandTest.php` (new texts)

**Interfaces:**
- Consumes: `VideoService::removeStoredFiles(Video): void` (Task 1), `VideoRepository::updateProcessingStatus(Video, ProcessingStatusEnum): bool` (exists)
- Produces: `PurgeDeletedVideosUseCase::handle(bool $dryRun = false): VideoPurgeResult` (signature unchanged)

- [ ] **Step 1: Rewrite the use case test**

Replace the whole content of `tests/Integration/Application/Cleanup/PurgeDeletedVideosUseCaseTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Cleanup;

use App\Application\Cleanup\PurgeDeletedVideosUseCase;
use App\Constants\Config\DefaultConfigEntry;
use App\Enum\ProcessingStatusEnum;
use App\Enum\StatusEnum;
use App\Facades\Cfg;
use App\Models\Assignment;
use App\Models\Download;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

final class PurgeDeletedVideosUseCaseTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Cfg::set(DefaultConfigEntry::POST_EXPIRY_RETENTION_WEEKS, 2, 'default', 'int');
    }

    public function testRemovesTheFilesButKeepsTheVideoWithItsHistory(): void
    {
        $video = $this->deletedVideo(weeksAgo: 3);
        $assignment = Assignment::factory()->forVideo($video)->create(['status' => StatusEnum::PICKEDUP->value]);
        $download = Download::factory()->forAssignment($assignment)->create();

        $result = app(PurgeDeletedVideosUseCase::class)->handle();

        self::assertSame(1, $result->purged);
        self::assertSame(0, $result->failed);
        Storage::disk('local')->assertMissing($video->path);
        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
        self::assertSame(ProcessingStatusEnum::Deleted, Video::withTrashed()->find($video->getKey())->processing_status);
        $this->assertDatabaseHas('assignments', ['id' => $assignment->getKey()]);
        $this->assertDatabaseHas('downloads', ['id' => $download->getKey()]);
    }

    public function testKeepsVideosStillWithinTheRetentionPeriod(): void
    {
        $video = $this->deletedVideo(weeksAgo: 1);

        self::assertSame(0, app(PurgeDeletedVideosUseCase::class)->handle()->purged);

        Storage::disk('local')->assertExists($video->path);
        self::assertNotSame(ProcessingStatusEnum::Deleted, Video::withTrashed()->find($video->getKey())->processing_status);
    }

    public function testNeverTouchesVideosThatAreNotDeleted(): void
    {
        $video = Video::factory()->create(['disk' => 'local', 'created_at' => now()->subYear()]);
        Storage::disk('local')->put($video->path, 'video-bytes');

        app(PurgeDeletedVideosUseCase::class)->handle();

        Storage::disk('local')->assertExists($video->path);
        $this->assertDatabaseHas('videos', ['id' => $video->getKey(), 'deleted_at' => null]);
    }

    public function testAPurgedVideoIsNotPickedUpAgain(): void
    {
        $this->deletedVideo(weeksAgo: 3);
        app(PurgeDeletedVideosUseCase::class)->handle();

        $second = app(PurgeDeletedVideosUseCase::class)->handle();

        self::assertSame(0, $second->purged);
        self::assertSame(0, $second->failed);
    }

    public function testFollowsTheConfiguredRetentionPeriod(): void
    {
        Cfg::set(DefaultConfigEntry::POST_EXPIRY_RETENTION_WEEKS, 5, 'default', 'int');
        $kept = $this->deletedVideo(weeksAgo: 4);
        $purged = $this->deletedVideo(weeksAgo: 6);

        app(PurgeDeletedVideosUseCase::class)->handle();

        Storage::disk('local')->assertExists($kept->path);
        Storage::disk('local')->assertMissing($purged->path);
    }

    public function testAVideoWhoseFileCannotBeRemovedIsRetriedWhileTheOthersArePurged(): void
    {
        $stuck = $this->deletedVideo(weeksAgo: 3, disk: 'broken');
        $purged = $this->deletedVideo(weeksAgo: 3);

        $result = app(PurgeDeletedVideosUseCase::class)->handle();

        self::assertSame(1, $result->purged);
        self::assertSame(1, $result->failed);
        self::assertNotSame(ProcessingStatusEnum::Deleted, Video::withTrashed()->find($stuck->getKey())->processing_status);
        Storage::disk('local')->assertMissing($purged->path);
        self::assertSame(1, app(PurgeDeletedVideosUseCase::class)->handle()->failed);
    }

    public function testDryRunChangesNothing(): void
    {
        $video = $this->deletedVideo(weeksAgo: 3);

        $result = app(PurgeDeletedVideosUseCase::class)->handle(dryRun: true);

        self::assertSame(1, $result->purged);
        Storage::disk('local')->assertExists($video->path);
        self::assertNotSame(ProcessingStatusEnum::Deleted, Video::withTrashed()->find($video->getKey())->processing_status);
    }

    private function deletedVideo(int $weeksAgo, string $disk = 'local'): Video
    {
        $video = Video::factory()->create(['disk' => $disk]);
        if ($disk === 'local') {
            Storage::disk('local')->put($video->path, 'video-bytes');
        }
        $video->delete();
        $video->forceFill(['deleted_at' => now()->subWeeks($weeksAgo)])->saveQuietly();

        return $video;
    }
}
```

In `tests/Integration/Commands/PurgeDeletedVideosCommandTest.php` replace the two expected texts:

```php
            ->expectsOutputToContain('Removed the files of 1 deleted video(s), 0 failed.')
```

```php
            ->expectsOutputToContain('Would remove the files of 1 deleted video(s).')
```

and in `testPurgesDeletedVideosAndReportsTheCount` replace `$this->assertDatabaseMissing('videos', ['id' => $video->getKey()]);` with:

```php
        Storage::disk('local')->assertMissing($video->path);
        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
```

- [ ] **Step 2: Run both tests to verify they fail**

Run: `docker exec -e XDEBUG_MODE=off dashclip-delivery-sharing-1 php artisan test --no-coverage tests/Integration/Application/Cleanup/PurgeDeletedVideosUseCaseTest.php`
and: `docker exec -e XDEBUG_MODE=off dashclip-delivery-sharing-1 php artisan test --no-coverage tests/Integration/Commands/PurgeDeletedVideosCommandTest.php`
Expected: failures, rows are hard-deleted and texts differ.

- [ ] **Step 3: Implement**

`app/Repository/VideoRepository.php`, replace the body of `lazyDeletedBefore()`:

```php
    public function lazyDeletedBefore(CarbonInterface $deletedBefore, int $chunkSize = 100): LazyCollection
    {
        return Video::onlyTrashed()
            ->where('deleted_at', '<=', $deletedBefore)
            ->where(static function (Builder $query): void {
                $query->whereNull('processing_status')
                    ->orWhere('processing_status', '!=', ProcessingStatusEnum::Deleted->value);
            })
            ->lazyById($chunkSize);
    }
```

and update its PHPDoc first line to: `Deleted videos whose files are still stored and whose deletion is at or before the given moment.`

`app/Application/Cleanup/PurgeDeletedVideosUseCase.php`:
- constructor becomes `public function __construct(private VideoRepository $videoRepository, private VideoService $videoService)`
- replace the `try` block inside the loop with:

```php
            try {
                $this->videoService->removeStoredFiles($video);
                $this->videoRepository->updateProcessingStatus($video, ProcessingStatusEnum::Deleted);
                $purged++;
            } catch (Throwable $exception) {
                Log::error('Files of a deleted video could not be removed', [
                    'video_id' => $video->getKey(),
                    'exception' => $exception,
                ]);
                $failed++;
            }
```

- delete the line `$removed ? $purged++ : $failed++;`
- class PHPDoc becomes:

```php
/**
 * Removes the stored files of videos that have been deleted for longer than the retention period.
 *
 * The video row, its clips, offers and downloads stay as history; processing_status "deleted" marks
 * that the files are gone, so every video is handled once. A video whose files cannot be removed
 * keeps its status and is retried on the next run.
 */
```

- imports: add `use App\Enum\ProcessingStatusEnum;` and `use App\Services\VideoService;`

`app/Console/Commands/PurgeDeletedVideosCommand.php`:
- description: `'Remove the stored files of deleted videos once the retention period has passed; their history stays'`
- dry run text: `"Would remove the files of {$result->purged} deleted video(s)."`
- normal text: `"Removed the files of {$result->purged} deleted video(s), {$result->failed} failed."`

- [ ] **Step 4: Run both tests to verify they pass**

Run: same two commands as Step 2. Expected: 7 passed and 3 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Repository/VideoRepository.php app/Application/Cleanup/PurgeDeletedVideosUseCase.php app/Console/Commands/PurgeDeletedVideosCommand.php tests/Integration/Application/Cleanup/PurgeDeletedVideosUseCaseTest.php tests/Integration/Commands/PurgeDeletedVideosCommandTest.php
git commit -m "fix(videos): purge only the files of deleted videos and keep their history (#375)

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 3: History relation and available offers without deleted videos

**Files:**
- Modify: `app/Models/Assignment.php` (`videoWithTrashed()` next to `video()`, `scopeAvailable()`)
- Test: `tests/Integration/Models/AssignmentDeletedVideoTest.php`

**Interfaces:**
- Produces: `App\Models\Assignment::videoWithTrashed(): BelongsTo` (includes soft-deleted videos)
- Produces: `Assignment::available()` scope excludes offers whose video is deleted

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use Tests\DatabaseTestCase;

final class AssignmentDeletedVideoTest extends DatabaseTestCase
{
    public function testHistoryRelationStillFindsADeletedVideo(): void
    {
        $assignment = Assignment::factory()->create(['status' => StatusEnum::PICKEDUP->value]);
        $assignment->video->delete();
        $assignment->refresh();

        self::assertNull($assignment->video);
        self::assertNotNull($assignment->videoWithTrashed);
        self::assertTrue($assignment->videoWithTrashed->trashed());
    }

    public function testAvailableOffersLeaveOutDeletedVideos(): void
    {
        $visible = Assignment::factory()->create(['status' => StatusEnum::QUEUED->value, 'expires_at' => now()->addDay()]);
        $hidden = Assignment::factory()->create(['status' => StatusEnum::QUEUED->value, 'expires_at' => now()->addDay()]);
        $hidden->video->delete();

        $ids = Assignment::query()->available()->pluck('id')->all();

        self::assertContains($visible->getKey(), $ids);
        self::assertNotContains($hidden->getKey(), $ids);
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `docker exec -e XDEBUG_MODE=off dashclip-delivery-sharing-1 php artisan test --no-coverage tests/Integration/Models/AssignmentDeletedVideoTest.php`
Expected: FAIL, `videoWithTrashed` undefined and the deleted offer is listed.

- [ ] **Step 3: Implement**

`app/Models/Assignment.php`, below `video()`:

```php
    /**
     * The video including a deleted one, for history views; offer logic keeps using video().
     * @return BelongsTo<Video, $this>
     */
    public function videoWithTrashed(): BelongsTo
    {
        return $this->belongsTo(Video::class, 'video_id')->withTrashed();
    }
```

In `scopeAvailable()`, add `->whereHas('video')` as the first call on `$query`:

```php
        return $query
            ->whereHas('video')
            ->whereIn('status', [
```

- [ ] **Step 4: Run the test and the affected suites**

Run: `docker exec -e XDEBUG_MODE=off dashclip-delivery-sharing-1 php artisan test --no-coverage tests/Integration/Models/AssignmentDeletedVideoTest.php` (2 passed), then
`docker exec -e XDEBUG_MODE=off dashclip-delivery-sharing-1 php artisan test --parallel --no-coverage tests/Feature/Standard/Pages`.
Expected: `MyOffersTest::testOffersWithDeletedVideosRenderWithoutAPreview` now fails (the offer is no longer listed under "available"); it is rewritten in Task 5. All other tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Models/Assignment.php tests/Integration/Models/AssignmentDeletedVideoTest.php
git commit -m "fix(offers): keep deleted videos out of available offers, add a history relation (#375)

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 4: Download history renders deleted videos

**Files:**
- Modify: `app/Repository/DownloadRepository.php` (`forUser()` eager load)
- Modify: `app/Filament/Standard/Pages/DownloadHistory.php`
- Modify: `lang/de/download_history.php`, `lang/en/download_history.php` (key `table.deleted`)
- Test: `tests/Feature/Filament/Standard/Pages/DownloadHistoryTest.php` (new test)

**Interfaces:**
- Consumes: `Assignment::videoWithTrashed()` (Task 3)

- [ ] **Step 1: Write the failing test** (append to `DownloadHistoryTest`)

```php
    public function testDownloadsOfDeletedVideosStayVisibleWithoutALink(): void
    {
        $video = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)
            ->create(['original_name' => 'Removed Clip.mp4']);
        $assignment = Assignment::factory()->forVideo($video)->create(['status' => 'picked_up']);
        $download = Download::factory()->forAssignment($assignment)->create();
        $video->delete();

        Livewire::test(DownloadHistory::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$download])
            ->assertSee('Removed Clip.mp4')
            ->assertSee(__('download_history.table.deleted'))
            ->assertTableActionHidden('view-video', $download);
    }
```

Note: `DownloadRepository::forUser()` filters by `hasUsersClips`; if the soft-deleted clips of the deleted video drop the download from the list, change that scope call to use clips including trashed ones as part of Step 3 (see below) and keep this test as the guard.

- [ ] **Step 2: Run it to verify it fails**

Run: `docker exec -e XDEBUG_MODE=off dashclip-delivery-sharing-1 php artisan test --no-coverage tests/Feature/Filament/Standard/Pages/DownloadHistoryTest.php`
Expected: FAIL (500 from `videoUrl()` with a null video, or the record is missing).

- [ ] **Step 3: Implement**

`lang/de/download_history.php`, inside `'table' => [`: `'deleted' => 'gelöscht',`
`lang/en/download_history.php`, inside `'table' => [`: `'deleted' => 'deleted',`

`app/Repository/DownloadRepository.php`, in `forUser()` replace `->with(['assignment.video', 'assignment.channel']);` with `->with(['assignment.videoWithTrashed', 'assignment.channel']);`. If Step 2 showed the record missing, inspect `Assignment::scopeHasUsersClips()` and make it match clips including soft-deleted ones for this query (e.g. `whereHas('videoWithTrashed', fn ($q) => $q->whereHas('clipsWithTrashed', fn ($c) => $c->where('user_id', $user->getKey())))`) instead of changing the shared scope.

`app/Filament/Standard/Pages/DownloadHistory.php`:
- title column:

```php
                TextColumn::make('assignment.videoWithTrashed.original_name')
                    ->label(__('download_history.table.columns.video'))
                    ->url(fn (Download $record): ?string => $this->videoUrl($record))
                    ->description(
                        fn (Download $record): ?string => $this->isVideoDeleted($record)
                            ? __('download_history.table.deleted')
                            : null
                    )
                    ->limit(60),
```

- action: add `->hidden(fn (Download $record): bool => $this->isVideoDeleted($record))` before `->url(...)`.
- helpers:

```php
    private function videoUrl(Download $record): ?string
    {
        if ($this->isVideoDeleted($record)) {
            return null;
        }

        return VideoResource::getUrl('view', ['record' => $record->assignment->videoWithTrashed]);
    }

    private function isVideoDeleted(Download $record): bool
    {
        return $record->assignment?->videoWithTrashed?->trashed() ?? true;
    }
```

- [ ] **Step 4: Run the test file to verify all pass**

Run: same as Step 2. Expected: all DownloadHistoryTest tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Repository/DownloadRepository.php app/Filament/Standard/Pages/DownloadHistory.php lang/de/download_history.php lang/en/download_history.php tests/Feature/Filament/Standard/Pages/DownloadHistoryTest.php
git commit -m "fix(downloads): keep downloads of deleted videos in the download history (#375)

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 5: "Meine Angebote" renders deleted videos

**Files:**
- Modify: `app/Filament/Standard/Pages/MyOffers/Table/AssignmentTable.php` (`baseQuery()` eager load)
- Modify: `app/Filament/Standard/Pages/MyOffers/Table/Columns.php` (`videoTitle()`, `uploaders()`, `resolveUploaders()`, preview column)
- Modify: `app/Filament/Standard/Pages/MyOffers/Table/Actions.php` (`downloadAgain()`, `download()`, `submit()`)
- Modify: `app/Filament/Standard/Pages/MyOffers.php` (`getDetailsInfolist()`)
- Modify: `lang/de/my_offers.php`, `lang/en/my_offers.php` (key `table.no_longer_available`)
- Test: `tests/Feature/Standard/Pages/MyOffersTest.php` (rewrite one test, add two)

**Interfaces:**
- Consumes: `Assignment::videoWithTrashed()` (Task 3), `Video::clipsWithTrashed()` (Task 1)

- [ ] **Step 1: Write the failing tests**

Replace `testOffersWithDeletedVideosRenderWithoutAPreview` in `MyOffersTest` and add a helper plus two tests:

```php
    public function testDownloadedOffersOfDeletedVideosRenderWithoutAPreview(): void
    {
        [$channel] = $this->actingOperator();
        $assignment = $this->downloadedOfferOfDeletedVideo($channel, 'Removed Clip.mp4');

        Livewire::test(MyOffers::class)
            ->set('activeTab', 'downloaded')
            ->assertCanSeeTableRecords([$assignment])
            ->assertSee('Removed Clip.mp4')
            ->assertSee(__('my_offers.table.no_longer_available'))
            ->assertTableActionHidden('download_again', $assignment);
    }

    public function testDetailsOfADeletedVideoOpenWithoutAPreview(): void
    {
        [$channel] = $this->actingOperator();
        $assignment = $this->downloadedOfferOfDeletedVideo($channel, 'Removed Clip.mp4');

        Livewire::test(MyOffers::class)
            ->set('activeTab', 'downloaded')
            ->mountTableAction('view_details', $assignment)
            ->assertSee('Removed Clip.mp4')
            ->assertDontSee(__('my_offers.modal.preview.heading'));
    }

    public function testAvailableOffersOfDeletedVideosAreNotListed(): void
    {
        [$channel] = $this->actingOperator();
        $assignment = Assignment::factory()->forChannel($channel)
            ->create(['status' => StatusEnum::QUEUED->value, 'expires_at' => now()->addDay()]);
        $assignment->video->delete();

        Livewire::test(MyOffers::class)
            ->assertCanNotSeeTableRecords([$assignment]);
    }

    /**
     * @return array{0: Channel, 1: User}
     */
    private function actingOperator(): array
    {
        $user = User::factory()->create();
        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles([RoleEnum::CHANNEL_OPERATOR->value]);
        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user, ['is_user_verified' => true]);
        Filament::setTenant($team, true);
        Filament::auth()->login($user);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        return [$channel, $user];
    }

    private function downloadedOfferOfDeletedVideo(Channel $channel, string $name): Assignment
    {
        $assignment = Assignment::factory()->forChannel($channel)->create(['status' => StatusEnum::PICKEDUP->value]);
        $assignment->video->update(['original_name' => $name]);
        Download::factory()->forAssignment($assignment)->create();
        $assignment->video->delete();

        return $assignment;
    }
```

Add `use App\Models\User;` if not imported (it is) and keep the existing imports.

- [ ] **Step 2: Run the tests to verify they fail**

Run: `docker exec -e XDEBUG_MODE=off dashclip-delivery-sharing-1 php artisan test --no-coverage tests/Feature/Standard/Pages/MyOffersTest.php`
Expected: the two downloaded-tab tests fail (500 in `resolveUploaders()` / `getDetailsInfolist()`); the available test passes already because of Task 3.

- [ ] **Step 3: Implement**

`lang/de/my_offers.php` inside `'table' => [`: `'no_longer_available' => 'nicht mehr verfügbar',`
`lang/en/my_offers.php` inside `'table' => [`: `'no_longer_available' => 'no longer available',`

`AssignmentTable::baseQuery()`: replace `->with(['video.clips.user', 'downloads', 'latestDownload'])` with
`->with(['videoWithTrashed.clipsWithTrashed.user', 'downloads', 'latestDownload'])`.

`Columns.php`:

```php
    public function videoTitle(): TextColumn
    {
        return TextColumn::make('videoWithTrashed.original_name')
            ->label(__('my_offers.table.columns.video_title'))
            ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                'videoWithTrashed',
                fn (Builder $video): Builder => $video->where('original_name', 'like', "%{$search}%")
            ))
            ->limit(40)
            ->description(
                fn (Assignment $record): ?string => $record->videoWithTrashed?->trashed()
                    ? __('my_offers.table.no_longer_available')
                    : null
            )
            ->tooltip(
                fn (Assignment $record): string => $record->videoWithTrashed->original_name ?? ''
            );
    }
```

The previous `->sortable()` on `video.original_name` is dropped because sorting by a relation column with `withTrashed` is not supported by the default relationship sort; check `testAvailableTabRendersExpectedColumns` and keep `->sortable(query: ...)` with a `orderBy(Video::withTrashed()->select('original_name')->whereColumn('videos.id', 'assignments.video_id'), $direction)` subquery if an existing test asserts sortability. Add `use Illuminate\Database\Eloquent\Builder;` and `use App\Models\Video;` as needed.

`uploaders()`: `TextColumn::make('videoWithTrashed.clipsWithTrashed.user.display_name')`.

`resolveUploaders()`:

```php
        return ($record->videoWithTrashed?->clipsWithTrashed ?? collect())
            ->pluck('user.display_name')
            ->unique()
            ->filter()
            ->implode(', ')
            ?: '-';
```

Preview column (`videoPreview()`): extend its `->visible(...)` closure so it stays as is, and make the Blade view receive nothing for deleted videos by adding
`->viewData(fn (Assignment $record): array => ['record' => $record->videoWithTrashed?->trashed() ? null : $record])`
if the column supports `viewData`; otherwise leave the column unchanged, because `video-preview.blade.php` already renders the "no preview" image when `$record->video` is null.

`Actions.php`: in `downloadAgain()` and `download()` replace the `->visible(...)` closures with

```php
            ->visible(
                fn (?Assignment $record): bool => $page->activeTab === 'downloaded'
                    && !($record?->videoWithTrashed?->trashed() ?? false)
            );
```

(`'available'` for `download()`), and in `submit()` add at the top of the visibility closure, after the null check:

```php
                if ($record->videoWithTrashed?->trashed()) {
                    return false;
                }
```

`MyOffers::getDetailsInfolist()`:
- at the start: `$video = $assignment->videoWithTrashed; $clips = $video?->clipsWithTrashed()->orderBy('start_sec')->get() ?? collect(); $isDeleted = $video?->trashed() ?? true;`
- state: `'video' => $video, 'clips' => $clips, 'note' => $assignment->note`
- preview section: add `->visible(! $isDeleted)` and pass `'video' => $video` to its `viewData`
- clips section: `'clips' => $clips`

- [ ] **Step 4: Run the MyOffers tests**

Run: `docker exec -e XDEBUG_MODE=off dashclip-delivery-sharing-1 php artisan test --no-coverage tests/Feature/Standard/Pages/MyOffersTest.php`
Expected: all pass (existing ones included).

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Standard/Pages/MyOffers.php app/Filament/Standard/Pages/MyOffers/Table/AssignmentTable.php app/Filament/Standard/Pages/MyOffers/Table/Columns.php app/Filament/Standard/Pages/MyOffers/Table/Actions.php lang/de/my_offers.php lang/en/my_offers.php tests/Feature/Standard/Pages/MyOffersTest.php
git commit -m "fix(offers): show downloaded offers of deleted videos in my offers (#375)

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 6: Administration lists render deleted videos

**Files:**
- Modify: `app/Filament/Admin/Resources/Assignments/AssignmentResource.php` (video column)
- Modify: `app/Filament/Admin/Resources/Downloads/DownloadResource.php` (video column)
- Modify: `lang/de/filament.php`, `lang/en/filament.php` (key `admin.labels.deleted`)
- Test: `tests/Integration/Filament/Admin/Resources/AssignmentResourceTest.php`, `DownloadResourceTest.php` (one test each)

**Interfaces:**
- Consumes: `Assignment::videoWithTrashed()` (Task 3)

- [ ] **Step 1: Write the failing tests**

`DownloadResourceTest`:

```php
    public function testListShowsDownloadsOfDeletedVideos(): void
    {
        $video = Video::factory()->create(['original_name' => 'Removed Admin Clip.mp4']);
        $assignment = Assignment::factory()->forVideo($video)->withBatch(Batch::factory()->type('assign')->create())
            ->create(['status' => 'picked_up']);
        $download = Download::factory()->forAssignment($assignment)->create();
        $video->delete();

        Livewire::test(ListDownloads::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$download])
            ->assertSee('Removed Admin Clip.mp4')
            ->assertSee(__('filament.admin.labels.deleted'));
    }
```

`AssignmentResourceTest` (add the imports `App\Models\Video`, `App\Models\User`, `Livewire\Livewire` if missing):

```php
    public function testListShowsOffersOfDeletedVideos(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $video = Video::factory()->create(['original_name' => 'Removed Admin Offer.mp4']);
        $assignment = Assignment::factory()->forVideo($video)->withBatch(Batch::factory()->type('assign')->create())->create();
        $video->delete();

        Livewire::test(ListAssignments::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$assignment])
            ->assertSee('Removed Admin Offer.mp4')
            ->assertSee(__('filament.admin.labels.deleted'));
    }
```

- [ ] **Step 2: Run them to verify they fail**

Run each file with `docker exec -e XDEBUG_MODE=off dashclip-delivery-sharing-1 php artisan test --no-coverage <file>`.
Expected: the download list crashes on `->getAttribute('video')->getAttribute('preview_url')`; the assignment list does not show the name.

- [ ] **Step 3: Implement**

`lang/de/filament.php` inside `'admin' => ['labels' => [`: `'deleted' => 'gelöscht',`
`lang/en/filament.php` same place: `'deleted' => 'deleted',`

`AssignmentResource`, video column:

```php
                TextColumn::make('videoWithTrashed.original_name')
                    ->label(__('filament.admin.labels.video'))
                    ->toggleable()
                    ->limit(40)
                    ->description(
                        fn (Assignment $assignment): ?string => $assignment->videoWithTrashed?->trashed()
                            ? __('filament.admin.labels.deleted')
                            : null
                    )
                    ->url(function (Assignment $assignment) {
                        $video = $assignment->video;
                        return $video ? VideoResource::getUrl('view', ['record' => $video]) : null;
                    })
                    ->openUrlInNewTab(),
```

`DownloadResource`, video column:

```php
                TextColumn::make('assignment.videoWithTrashed.original_name')
                    ->url(fn (Download $download): ?string => $download->assignment?->video?->preview_url, true)
                    ->description(
                        fn (Download $download): ?string => $download->assignment?->videoWithTrashed?->trashed()
                            ? __('filament.admin.labels.deleted')
                            : null
                    )
                    ->label(__('filament.admin.labels.video'))
                    ->sortable(),
```

If `->sortable()` fails for the relation path, keep sorting via a subquery like in Task 5 or drop it only if no test asserts it.

- [ ] **Step 4: Run both test files**

Expected: all tests in both files pass.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Admin/Resources/Assignments/AssignmentResource.php app/Filament/Admin/Resources/Downloads/DownloadResource.php lang/de/filament.php lang/en/filament.php tests/Integration/Filament/Admin/Resources/AssignmentResourceTest.php tests/Integration/Filament/Admin/Resources/DownloadResourceTest.php
git commit -m "fix(admin): list offers and downloads of deleted videos (#375)

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 7: Privacy policy, changelog, full verification, PR

**Files:**
- Modify: `resources/views/datenschutz.blade.php` (line with "Gelöschte Videos und Vorschauen werden vollständig entfernt." and the retention overview line "Videoinhalte: bis zur Entfernung")
- Modify: `CHANGELOG.md`
- Test: existing `tests/Feature/Http/*Privacy*` tests must still pass

- [ ] **Step 1: Update the privacy policy**

Replace `Gelöschte Videos und Vorschauen werden vollständig entfernt.` with:

```
                Videodateien und Vorschauen gelöschter Videos werden nach Ablauf einer Aufbewahrungsfrist
                entfernt. Angaben zur Verteilung (Titel, Kanal, Zeitpunkte von Angeboten und Downloads)
                bleiben zur Nachvollziehbarkeit erhalten.
```

Replace `<li><strong>Videoinhalte:</strong> bis zur Entfernung</li>` with:

```
                <li><strong>Videodateien:</strong> bis zur Löschung zuzüglich Aufbewahrungsfrist</li>
                <li><strong>Angaben zur Verteilung gelöschter Videos:</strong> gemäß Audit-Erfordernissen</li>
```

- [ ] **Step 2: Changelog under `## [Unreleased]`**

Add under `### Fixed`:

```
- **Removing deleted videos keeps their history**: The daily removal of deleted videos now deletes only
  their files and previews. The video entry, its offers and downloads stay, so download histories and
  "Meine Angebote" keep showing them as no longer available. Views that crashed on a deleted video (download
  history, downloaded offers and their details, administration lists) render it now, and offers of
  deleted videos no longer show as available. The privacy policy describes the retention accordingly.
```

Keep every line at most 120 characters.

- [ ] **Step 3: Full suites and Pint**

Run:
`docker exec -e XDEBUG_MODE=off dashclip-delivery-sharing-1 php artisan test --parallel --no-coverage tests/Feature`
`docker exec -e XDEBUG_MODE=off dashclip-delivery-sharing-1 php artisan test --parallel --no-coverage tests/Integration`
`docker exec dashclip-delivery-sharing-1 ./vendor/bin/pint --test <every changed PHP file>`
Expected: all green (the WebP test may be skipped locally until the container is rebuilt); Pint passes for the changed files.

- [ ] **Step 4: Commit and push**

```bash
git add resources/views/datenschutz.blade.php CHANGELOG.md
git commit -m "docs(privacy): describe the retention of deleted videos (#375)

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
git push
```

- [ ] **Step 5: Open the PR against `4.x-dev`**

Title: `fix(videos): gelöschte Videos entfernen, Historie behalten (#375, Teil 1)`. Body: summary of the behaviour change, the deploy note from the spec, the fixed crashes, the privacy wording for approval, the conflict note about PR #363, and the test results. End with `🤖 Generated with [Claude Code](https://claude.com/claude-code)`.
