# WebDAV Ingest Integration — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Wire the existing WebDAV server into the existing ingest pipeline so that ZIP archives uploaded via WebDAV are automatically extracted and each video file enters the pipeline via the canonical `VideoQueuedForIngest` event.

**Architecture:** A `ZipUploadedListener` listens to `FileCreatedEvent` / `FileUpdatedEvent` from the WebDAV package and dispatches a `ProcessWebDavZipJob`. The job extracts the ZIP (using an extended `UnzipService`), creates `Video` records on the `import` disk via the existing `VideoService`, and fires `VideoQueuedForIngest` — the single entry point into the ingest pipeline. Two companion Artisan commands handle periodic fallback scanning and non-ZIP cleanup.

**Tech Stack:** Laravel 11, Filament, `n3xt0r/laravel-webdav-server` (FileCreatedEvent / FileUpdatedEvent), `n3xt0r/laravel-webdav-server-filament` (WebDavAccountCreatedEvent), existing `UnzipService`, `VideoService`, `VideoQueuedForIngest` event.

---

## Existing code to understand before touching anything

| File | Why relevant |
|------|-------------|
| `app/Services/Zip/UnzipService.php` | `safeExtract()` is private — needs to become protected or a public wrapper added |
| `app/Services/Contracts/UnzipServiceInterface.php` | Must add `extractSingle()` here first |
| `app/Services/VideoService.php` | `createVideoBydDiskAndFileInfoDto()` creates a Video + returns it with `wasRecentlyCreated` |
| `app/DTO/FileInfoDto.php` | `FileInfoDto::fromPath(string $path)` builds the DTO from a relative path |
| `app/Events/Video/VideoQueuedForIngest.php` | Extends `VideoCreated(Video $video, ?User $user)` — this is the pipeline entry point |
| `app/Listeners/DispatchVideoIngestJobListener.php` | Already wired: `VideoQueuedForIngest` → `ProcessVideoIngestJob` |
| `vendor/n3xt0r/laravel-webdav-server/src/Events/WebDav/FileCreatedEvent.php` | `readonly class` extending `FileEvent(disk, path, principal, bytes)` |
| `vendor/n3xt0r/laravel-webdav-server/src/ValueObjects/WebDavPrincipalValueObject.php` | `id`, `displayName`, `user: ?Authenticatable` |
| `config/webdav-server.php` | disk=`import`, root=`webdav` — user dir = `webdav/{principal.id}/` |

## File map

| Action | Path | Responsibility |
|--------|------|----------------|
| **Modify** | `app/Services/Contracts/UnzipServiceInterface.php` | add `extractSingle(string, string): bool` |
| **Modify** | `app/Services/Zip/UnzipService.php` | make `safeExtract` protected, implement `extractSingle` |
| **Create** | `app/Listeners/WebDav/ZipUploadedListener.php` | handle `FileCreatedEvent` / `FileUpdatedEvent`, dispatch job |
| **Create** | `app/Jobs/ProcessWebDavZipJob.php` | extract ZIP, create Video records, fire `VideoQueuedForIngest` |
| **Create** | `app/Console/Commands/IngestWebDavCommand.php` | periodic fallback: scan all WebDAV dirs for unprocessed ZIPs |
| **Create** | `app/Console/Commands/CleanWebDavNonZipCommand.php` | remove non-ZIP files from WebDAV user dirs |
| **Modify** | `app/Providers/AppServiceProvider.php` | register `ZipUploadedListener` for both WebDAV file events |
| **Modify** | `routes/console.php` | schedule `ingest:webdav` and `clean:webdav-non-zip` |
| **Create** | `tests/Unit/Listeners/WebDav/ZipUploadedListenerTest.php` | |
| **Create** | `tests/Integration/Jobs/ProcessWebDavZipJobTest.php` | |
| **Create** | `tests/Unit/Commands/CleanWebDavNonZipCommandTest.php` | |

---

## Task 1: Extend UnzipServiceInterface and UnzipService with `extractSingle`

**Files:**
- Modify: `app/Services/Contracts/UnzipServiceInterface.php`
- Modify: `app/Services/Zip/UnzipService.php`
- Test: `tests/Unit/Services/Zip/UnzipServiceTest.php` (likely exists — add a test method)

- [ ] **Step 1: Write the failing test**

Check if `tests/Unit/Services/Zip/UnzipServiceTest.php` exists. If not, create it. Add:

```php
public function testExtractSingleExtractsAndDeletesZip(): void
{
    $fs = new Filesystem();
    $service = new UnzipService($fs);

    $tmpDir = sys_get_temp_dir() . '/unzip_test_' . uniqid();
    mkdir($tmpDir, 0755, true);

    // Create a real zip with one safe file
    $zipPath = $tmpDir . '/test.zip';
    $zip = new \ZipArchive();
    $zip->open($zipPath, \ZipArchive::CREATE);
    $zip->addFromString('video.mp4', 'fake-video-content');
    $zip->close();

    $result = $service->extractSingle($zipPath, $tmpDir);

    $this->assertTrue($result);
    $this->assertFileDoesNotExist($zipPath);       // ZIP deleted after extraction
    $this->assertFileExists($tmpDir . '/video.mp4'); // content extracted

    // Cleanup
    (new Filesystem())->deleteDirectory($tmpDir);
}
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test tests/Unit/Services/Zip/UnzipServiceTest.php --filter testExtractSingleExtractsAndDeletesZip
```

Expected: FAIL — `Call to undefined method extractSingle()`

- [ ] **Step 3: Add `extractSingle` to the interface**

```php
// app/Services/Contracts/UnzipServiceInterface.php
/**
 * Extract a single ZIP archive into $targetDir, then delete the archive.
 * Returns true on success, false on open/extract failure or no safe entries.
 *
 * @throws \InvalidArgumentException if $zipPath does not exist
 */
public function extractSingle(string $absoluteZipPath, string $absoluteTargetDir): bool;
```

- [ ] **Step 4: Implement in UnzipService**

Change `safeExtract` visibility from `private` to `protected`, then add the public method:

```php
// app/Services/Zip/UnzipService.php — change visibility
protected function safeExtract(string $zipPath, string $targetDir): string { ... }

// Add after unzipDirectory():
public function extractSingle(string $absoluteZipPath, string $absoluteTargetDir): bool
{
    if (!file_exists($absoluteZipPath)) {
        throw new \InvalidArgumentException("ZIP not found: {$absoluteZipPath}");
    }

    $result = $this->safeExtract($absoluteZipPath, $absoluteTargetDir);

    if ($result === SafeExtractResult::EXTRACTED) {
        @unlink($absoluteZipPath);
        return true;
    }

    return false;
}
```

- [ ] **Step 5: Run test to confirm it passes**

```bash
php artisan test tests/Unit/Services/Zip/UnzipServiceTest.php --filter testExtractSingleExtractsAndDeletesZip
```

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Services/Contracts/UnzipServiceInterface.php app/Services/Zip/UnzipService.php tests/Unit/Services/Zip/UnzipServiceTest.php
git commit -m "feat(ingest): add UnzipService::extractSingle for single-file extraction"
```

---

## Task 2: ZipUploadedListener

**Files:**
- Create: `app/Listeners/WebDav/ZipUploadedListener.php`
- Create: `tests/Unit/Listeners/WebDav/ZipUploadedListenerTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Unit/Listeners/WebDav/ZipUploadedListenerTest.php
declare(strict_types=1);

namespace Tests\Unit\Listeners\WebDav;

use App\Jobs\ProcessWebDavZipJob;
use App\Listeners\WebDav\ZipUploadedListener;
use Illuminate\Support\Facades\Queue;
use Mockery;
use N3XT0R\LaravelWebdavServer\Events\WebDav\FileCreatedEvent;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use Tests\TestCase;

final class ZipUploadedListenerTest extends TestCase
{
    public function testDispatchesJobForZipFile(): void
    {
        Queue::fake();

        $principal = new WebDavPrincipalValueObject('user-42', 'John', null);
        $event = new FileCreatedEvent('import', 'webdav/user-42/archive.zip', $principal, 1024);

        app(ZipUploadedListener::class)->handle($event);

        Queue::assertPushed(ProcessWebDavZipJob::class, function ($job) {
            return $job->disk === 'import'
                && $job->path === 'webdav/user-42/archive.zip';
        });
    }

    public function testIgnoresNonZipFile(): void
    {
        Queue::fake();

        $principal = new WebDavPrincipalValueObject('user-42', 'John', null);
        $event = new FileCreatedEvent('import', 'webdav/user-42/video.mp4', $principal, 1024);

        app(ZipUploadedListener::class)->handle($event);

        Queue::assertNothingPushed();
    }
}
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test tests/Unit/Listeners/WebDav/ZipUploadedListenerTest.php
```

Expected: FAIL — class not found

- [ ] **Step 3: Implement the listener**

```php
<?php
// app/Listeners/WebDav/ZipUploadedListener.php
declare(strict_types=1);

namespace App\Listeners\WebDav;

use App\Jobs\ProcessWebDavZipJob;
use N3XT0R\LaravelWebdavServer\Events\WebDav\FileCreatedEvent;
use N3XT0R\LaravelWebdavServer\Events\WebDav\FileUpdatedEvent;

final class ZipUploadedListener
{
    public function handle(FileCreatedEvent|FileUpdatedEvent $event): void
    {
        if (!str_ends_with(strtolower($event->path), '.zip')) {
            return;
        }

        ProcessWebDavZipJob::dispatch(
            disk: $event->disk,
            path: $event->path,
            userId: $event->principal->user?->getKey(),
        );
    }
}
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
php artisan test tests/Unit/Listeners/WebDav/ZipUploadedListenerTest.php
```

Expected: PASS (ProcessWebDavZipJob stub not needed as Queue::fake() intercepts dispatch)

- [ ] **Step 5: Commit**

```bash
git add app/Listeners/WebDav/ZipUploadedListener.php tests/Unit/Listeners/WebDav/ZipUploadedListenerTest.php
git commit -m "feat(ingest): add ZipUploadedListener for WebDAV file events"
```

---

## Task 3: ProcessWebDavZipJob

**Files:**
- Create: `app/Jobs/ProcessWebDavZipJob.php`
- Create: `tests/Integration/Jobs/ProcessWebDavZipJobTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Integration/Jobs/ProcessWebDavZipJobTest.php
declare(strict_types=1);

namespace Tests\Integration\Jobs;

use App\Events\Video\VideoQueuedForIngest;
use App\Jobs\ProcessWebDavZipJob;
use App\Models\User;
use App\Services\Contracts\UnzipServiceInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\DatabaseTestCase;

final class ProcessWebDavZipJobTest extends DatabaseTestCase
{
    public function testExtractsZipAndFiresIngestEvent(): void
    {
        Event::fake([VideoQueuedForIngest::class]);
        Storage::fake('import');

        // Put a fake ZIP and a fake extracted video on the disk
        Storage::disk('import')->put('webdav/42/archive.zip', 'zip-content');
        Storage::disk('import')->put('webdav/42/video.mp4', 'video-content');

        $unzip = Mockery::mock(UnzipServiceInterface::class);
        $unzip->shouldReceive('extractSingle')
            ->once()
            ->andReturnUsing(function (string $zip, string $dir) {
                // The fake disk already has video.mp4 — simulate extraction success
                return true;
            });

        $this->app->instance(UnzipServiceInterface::class, $unzip);

        $user = User::factory()->create();

        ProcessWebDavZipJob::dispatchSync(
            disk: 'import',
            path: 'webdav/42/archive.zip',
            userId: $user->id,
        );

        Event::assertDispatched(VideoQueuedForIngest::class, function ($event) {
            return $event->video->path === 'webdav/42/video.mp4';
        });
    }

    public function testSkipsIfZipExtractionFails(): void
    {
        Event::fake([VideoQueuedForIngest::class]);
        Storage::fake('import');
        Storage::disk('import')->put('webdav/42/broken.zip', 'bad-zip');

        $unzip = Mockery::mock(UnzipServiceInterface::class);
        $unzip->shouldReceive('extractSingle')->once()->andReturn(false);
        $this->app->instance(UnzipServiceInterface::class, $unzip);

        ProcessWebDavZipJob::dispatchSync(
            disk: 'import',
            path: 'webdav/42/broken.zip',
            userId: null,
        );

        Event::assertNotDispatched(VideoQueuedForIngest::class);
    }
}
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test tests/Integration/Jobs/ProcessWebDavZipJobTest.php
```

Expected: FAIL — class not found

- [ ] **Step 3: Implement the job**

```php
<?php
// app/Jobs/ProcessWebDavZipJob.php
declare(strict_types=1);

namespace App\Jobs;

use App\DTO\FileInfoDto;
use App\Events\Video\VideoQueuedForIngest;
use App\Models\User;
use App\Services\Contracts\UnzipServiceInterface;
use App\Services\VideoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

final class ProcessWebDavZipJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $disk,
        public readonly string $path,
        public readonly int|string|null $userId,
    ) {
    }

    public function handle(UnzipServiceInterface $unzip, VideoService $videoService): void
    {
        $storage = Storage::disk($this->disk);
        $absoluteZip = $storage->path($this->path);
        $absoluteDir = dirname($absoluteZip);

        $ok = $unzip->extractSingle($absoluteZip, $absoluteDir);

        if (!$ok) {
            Log::warning('WebDAV ZIP extraction failed', ['path' => $this->path, 'disk' => $this->disk]);
            return;
        }

        $user = $this->userId ? User::find($this->userId) : null;
        $userDir = dirname($this->path); // e.g. 'webdav/42'

        foreach ($storage->files($userDir) as $relativePath) {
            if (str_ends_with(strtolower($relativePath), '.zip')) {
                continue; // skip any remaining ZIPs
            }

            $fileInfo = FileInfoDto::fromPath($relativePath);
            $video = $videoService->createVideoBydDiskAndFileInfoDto($this->disk, $storage, $fileInfo);

            if ($video->wasRecentlyCreated) {
                VideoQueuedForIngest::dispatch($video, $user);
            }
        }
    }
}
```

- [ ] **Step 4: Run tests to confirm they pass**

```bash
php artisan test tests/Integration/Jobs/ProcessWebDavZipJobTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/ProcessWebDavZipJob.php tests/Integration/Jobs/ProcessWebDavZipJobTest.php
git commit -m "feat(ingest): add ProcessWebDavZipJob to extract ZIPs and trigger ingest pipeline"
```

---

## Task 4: Register events in AppServiceProvider

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Add the two imports and the listener registration in `boot()`**

Add to the top imports block:
```php
use App\Listeners\WebDav\ZipUploadedListener;
use Illuminate\Support\Facades\Event;
use N3XT0R\LaravelWebdavServer\Events\WebDav\FileCreatedEvent;
use N3XT0R\LaravelWebdavServer\Events\WebDav\FileUpdatedEvent;
```

Inside `boot()`:
```php
Event::listen([FileCreatedEvent::class, FileUpdatedEvent::class], ZipUploadedListener::class);
```

- [ ] **Step 2: Verify wiring with a quick smoke test**

```bash
php artisan event:list | grep -i "FileCreated\|FileUpdated"
```

Expected output contains `FileCreatedEvent` and `FileUpdatedEvent` mapped to `ZipUploadedListener`.

- [ ] **Step 3: Commit**

```bash
git add app/Providers/AppServiceProvider.php
git commit -m "feat(ingest): register ZipUploadedListener for WebDAV FileCreatedEvent and FileUpdatedEvent"
```

---

## Task 5: CleanWebDavNonZipCommand

**Files:**
- Create: `app/Console/Commands/CleanWebDavNonZipCommand.php`
- Create: `tests/Unit/Commands/CleanWebDavNonZipCommandTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Unit/Commands/CleanWebDavNonZipCommandTest.php
declare(strict_types=1);

namespace Tests\Unit\Commands;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CleanWebDavNonZipCommandTest extends TestCase
{
    public function testDeletesNonZipFilesFromWebDavDirectories(): void
    {
        Storage::fake('import');
        Storage::disk('import')->put('webdav/1/video.mp4', 'content');
        Storage::disk('import')->put('webdav/1/archive.zip', 'content');
        Storage::disk('import')->put('webdav/2/photo.jpg', 'content');

        $this->artisan('clean:webdav-non-zip')->assertExitCode(0);

        Storage::disk('import')->assertMissing('webdav/1/video.mp4');
        Storage::disk('import')->assertExists('webdav/1/archive.zip');
        Storage::disk('import')->assertMissing('webdav/2/photo.jpg');
    }
}
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test tests/Unit/Commands/CleanWebDavNonZipCommandTest.php
```

Expected: FAIL

- [ ] **Step 3: Implement the command**

```php
<?php
// app/Console/Commands/CleanWebDavNonZipCommand.php
declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class CleanWebDavNonZipCommand extends Command
{
    protected $signature = 'clean:webdav-non-zip {--disk=import : Storage disk to clean}';

    protected $description = 'Delete non-ZIP files from WebDAV user directories';

    public function handle(): int
    {
        $disk = (string) $this->option('disk');
        $storage = Storage::disk($disk);
        $root = config('webdav-server.storage.spaces.default.root', 'webdav');
        $deleted = 0;

        foreach ($storage->directories($root) as $userDir) {
            foreach ($storage->files($userDir) as $file) {
                if (!str_ends_with(strtolower($file), '.zip')) {
                    $storage->delete($file);
                    $deleted++;
                }
            }
        }

        $this->info("Deleted {$deleted} non-ZIP file(s) from WebDAV directories.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
php artisan test tests/Unit/Commands/CleanWebDavNonZipCommandTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/CleanWebDavNonZipCommand.php tests/Unit/Commands/CleanWebDavNonZipCommandTest.php
git commit -m "feat(ingest): add clean:webdav-non-zip command to remove invalid uploads"
```

---

## Task 6: IngestWebDavCommand (periodic fallback)

This replaces `ingest:scan` for the WebDAV source — it picks up any ZIPs that were uploaded while the queue was down or before the listener was active.

**Files:**
- Create: `app/Console/Commands/IngestWebDavCommand.php`

- [ ] **Step 1: Implement the command**

No new test class needed — the command delegates entirely to `ProcessWebDavZipJob` (already tested). Add an integration smoke test in the same test file as a quick sanity check.

```php
<?php
// app/Console/Commands/IngestWebDavCommand.php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessWebDavZipJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class IngestWebDavCommand extends Command
{
    protected $signature = 'ingest:webdav {--disk=import : Storage disk to scan}';

    protected $description = 'Scan all WebDAV user directories for unprocessed ZIP archives and queue them for ingest';

    public function handle(): int
    {
        $disk = (string) $this->option('disk');
        $storage = Storage::disk($disk);
        $root = config('webdav-server.storage.spaces.default.root', 'webdav');
        $queued = 0;

        foreach ($storage->directories($root) as $userDir) {
            foreach ($storage->files($userDir) as $file) {
                if (str_ends_with(strtolower($file), '.zip')) {
                    ProcessWebDavZipJob::dispatch(disk: $disk, path: $file, userId: null);
                    $queued++;
                }
            }
        }

        $this->info("Queued {$queued} ZIP archive(s) for ingest.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Verify command is registered**

```bash
php artisan list | grep ingest:webdav
```

Expected: `ingest:webdav` listed

- [ ] **Step 3: Commit**

```bash
git add app/Console/Commands/IngestWebDavCommand.php
git commit -m "feat(ingest): add ingest:webdav command as periodic fallback scanner"
```

---

## Task 7: Register commands in scheduler

**Files:**
- Modify: `routes/console.php`

- [ ] **Step 1: Add both commands to the scheduler**

Add after the existing `# Cleanup` block:

```php
# WebDAV ingest
Schedule::command(Commands\IngestWebDavCommand::class)->everyFifteenMinutes();
Schedule::command(Commands\CleanWebDavNonZipCommand::class)->hourly();
```

Add the imports at the top with the other `use` statements:
```php
// already present: use App\Console\Commands;
// Commands are resolved via Commands\IngestWebDavCommand::class — no extra use needed
```

- [ ] **Step 2: Verify**

```bash
php artisan schedule:list | grep -E "webdav|non-zip"
```

Expected: both commands appear in the schedule.

- [ ] **Step 3: Commit**

```bash
git add routes/console.php
git commit -m "feat(ingest): schedule ingest:webdav and clean:webdav-non-zip"
```

---

## Task 8: Changelog entry

**Files:**
- Modify: `CHANGELOG.md`

- [ ] **Step 1: Add to `[Unreleased]` → `### Added`**

```markdown
- **WebDAV ingest pipeline integration**
    - `ZipUploadedListener` now handles `FileCreatedEvent` and `FileUpdatedEvent` from the WebDAV
      package and dispatches `ProcessWebDavZipJob` for every `.zip` upload
    - `ProcessWebDavZipJob` extracts the archive via `UnzipService::extractSingle()`, creates `Video`
      records on the `import` disk, and fires `VideoQueuedForIngest` for each new file — the single
      entry point into the existing ingest pipeline
    - `UnzipService` extended with `extractSingle(string $absoluteZipPath, string $absoluteTargetDir): bool`
      for single-archive extraction with automatic deletion on success
    - `ingest:webdav` Artisan command scans all WebDAV user directories for unprocessed ZIP archives
      and queues them; acts as periodic fallback replacing the old `ingest:scan` entry point for
      the WebDAV-based import source; scheduled every 15 minutes
    - `clean:webdav-non-zip` Artisan command removes non-ZIP files from WebDAV user directories;
      scheduled hourly
```

- [ ] **Step 2: Commit**

```bash
git add CHANGELOG.md
git commit -m "docs(changelog): document WebDAV ingest pipeline integration"
```

---

## Self-review against spec

| Requirement | Covered by |
|-------------|-----------|
| WebDAV server endpoint | Already exists (package + config) |
| Authenticated access, user-scoped folders | Already exists (`WebDavPathPolicy`, config `root=webdav`) |
| ZIP-only uploads | `WebDavPathPolicy.createFile/write` (already implemented) |
| ZIP extracted locally before ingest | Task 3 (`ProcessWebDavZipJob`) |
| Non-ZIP uploads removed by command | Task 5 (`clean:webdav-non-zip`) |
| Dedicated command replaces `ingest:scan` | Task 6 (`ingest:webdav`) |
| Integrates with `VideoQueuedForIngest` | Task 3 — fires the canonical event |
| Filament: user can create/regenerate credentials | Already wired (`withUserAccountResource()` in PanelUserPanelProvider) |
| Filament: view WebDAV URL, see credentials | Already provided by `UserWebDavAccountResource` in the package |
| Table supports `max_archive_size`, `max_file_count` | In spec as future columns — not in existing migration; out of scope per spec "not enforced initially" |
| `last_used_at` column | Not in existing migration — out of scope for this plan |

**Gap:** `last_used_at` and `max_archive_size`/`max_file_count` are mentioned in the data model spec but the existing migration (`2026_05_04_193919_create_webdav_accounts_table.php`) does not include them. The spec says limits are "not enforced initially" — a follow-up migration can add them without changing this plan.