# Offer Downloads Through the Export Action Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver offer downloads on "Meine Angebote" through Filament's export action (queued job, database notification with a "ZIP herunterladen" button) while keeping the ZIP content identical: video file(s) plus today's `info.csv`, undeliverable videos skipped and logged.

**Architecture:** `ExportBulkAction`/`ExportAction` with an `OfferExporter` create a Filament `Export`; a custom `BuildOfferExportZipJob` replaces Filament's CSV preparation and packs the archive through `ZipService::buildArchive()` into the export directory; a custom `OfferExportFormatEnum` adds the notification button pointing to an application route served by `OfferExportDownloadController`, which marks the packed offers as downloaded. File conventions live in `OfferExportFileService`.

**Tech Stack:** PHP 8.5, Laravel, Filament 5 actions/exports, Livewire, PHPUnit via `docker exec dashclip-delivery-sharing-1 php artisan test --no-coverage <path>`.

**Spec:** `docs/superpowers/specs/2026-09-19-offer-download-export-design.md`

## Global Constraints

- Branch `feature/offer-download-export`; commits follow ADR 0007 (Conventional Commits) and end with `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`.
- Class names follow ADR 0002 suffixes: `*Job`, `*Enum`, `*Controller`, `*Service`, `*Exception`; the Filament exporter keeps the framework name `OfferExporter`.
- Exceptions follow ADR 0004 (application hierarchy, no raw global exceptions).
- `info.csv` content must stay byte-identical to `CsvService::buildInfoCsv()` for the packed offers.
- User-facing texts in German and English, no framework or internal terms, no em-dashes.
- Logs go to the default log channel (`Log::warning`).
- Only "Meine Angebote" changes; the public offer page keeps `ZipDownloader.js`, `zips.start`, `zips.progress`, `zips.download`, `OfferDownloadPreparationService`, `BuildZipJob`.
- Any change under `resources/js` requires `npm run build` and committing `public/build`.
- CHANGELOG entries go under `## [Unreleased]` (re-read the file first), Keep a Changelog order.
- Run tests only for the touched paths; the full suite is CI's job.

## File Structure

| File | Responsibility |
|---|---|
| `database/migrations/*_create_job_batches_table.php` (new, generated) | Laravel batch table needed by `Bus::batch` |
| `database/migrations/*_create_exports_table.php` (new, published) | Filament `exports` table |
| `app/Services/Zip/ZipService.php` (modify) | new `buildArchive()`; `build()` delegates to it |
| `app/Services/OfferExportFileService.php` (new) | where the ZIP and `packed.json` of an export live; read/write/delete |
| `app/Filament/Standard/Exports/OfferExporter.php` (new) | exporter: required columns, format, file name, completion texts |
| `app/Enum/OfferExportFormatEnum.php` (new) | ZIP format: notification button, downloader |
| `app/Filament/Standard/Exports/OfferExportZipDownloader.php` (new) | streams the export's ZIP |
| `app/Http/Controllers/OfferExportDownloadController.php` (new) | authorizes, marks packed offers, delivers |
| `app/Jobs/BuildOfferExportZipJob.php` (new) | builds the archive for an export |
| `app/Filament/Standard/Pages/MyOffers/Table/BulkActions.php`, `.../Table/Actions.php` (modify) | export actions |
| `app/Filament/Standard/Pages/MyOffers/Table/OfferExportActionConfigurator.php` (new) | shared configuration of the three export actions |
| `app/Console/Commands/CleanExpiredZipsCommand.php` (modify) | prune offer exports older than 24 h |
| `lang/{de,en}/my_offers.php`, `lang/vendor/filament-actions/{de,en}/export.php` | texts |
| Removed: `app/Application/Offer/DispatchZipDownload.php`, `resources/views/filament/standard/components/zip-form-anchor.blade.php`, `MyOffers::dispatchZipDownload()`, zip parts of `resources/js/app.js`, route `zips.channel.start`, `ZipController::startForChannel()`, `LinkService::getZipSelectedUrlForChannel()` |

---

### Task 1: Batch and export tables

**Files:**
- Create: `database/migrations/<timestamp>_create_job_batches_table.php` (generated)
- Create: `database/migrations/<timestamp>_create_exports_table.php` (published from Filament)

**Interfaces:**
- Produces: tables `job_batches` and `exports` (columns `id`, `completed_at`, `file_disk`, `file_name`, `exporter`, `processed_rows`, `total_rows`, `successful_rows`, `user_id`, timestamps).

- [ ] **Step 1: Generate the batch table migration**

Run: `docker exec dashclip-delivery-sharing-1 php artisan make:queue-batches-table`
Expected: `Migration created successfully.` and a new `*_create_job_batches_table.php`.

- [ ] **Step 2: Publish Filament's action migrations and keep only the exports table**

Run: `docker exec dashclip-delivery-sharing-1 php artisan vendor:publish --tag=filament-actions-migrations`
Then delete the published `*_create_imports_table.php` and `*_create_failed_import_rows_table.php` (imports are not used) and keep `*_create_exports_table.php`.

- [ ] **Step 3: Verify both migrations run in the test database**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --no-coverage tests/Feature/Standard/Pages/MyOffersTest.php`
Expected: PASS (RefreshDatabase runs the new migrations without errors).

- [ ] **Step 4: Commit**

```bash
git add database/migrations
git commit -m "build(exports): add batch and export tables"
```

---

### Task 2: `ZipService::buildArchive()`

**Files:**
- Modify: `app/Services/Zip/ZipService.php` (`build()`, `createZipArchive()`, remove `finalizeZip()`)
- Test: `tests/Integration/Services/ZipServiceTest.php`

**Interfaces:**
- Produces: `public function buildArchive(string $absolutePath, Channel $channel, Collection $items, string $jobId): Collection` returning the packed `Assignment`s; throws `ZipEmptyException` when nothing could be packed and `ZipBuildException` when the archive cannot be written. Skipped videos are logged as today with `job_id` = `$jobId`.

- [ ] **Step 1: Write the failing test** (append to `ZipServiceTest`)

```php
    public function testBuildArchiveWritesPackedOffersAndTheirInfoCsvToTheGivenPath(): void
    {
        $channel = Channel::factory()->create();
        Storage::put('videos/good.mp4', 'good-video');
        $good = Assignment::factory()->for($channel, 'channel')->for(Video::factory()->create([
            'disk' => 'local', 'path' => 'videos/good.mp4', 'bytes' => 10, 'original_name' => 'good.mp4',
        ]), 'video')->create();
        $missing = Assignment::factory()->for($channel, 'channel')->for(Video::factory()->create([
            'disk' => 'local', 'path' => 'videos/missing.mp4', 'bytes' => 10, 'original_name' => 'missing.mp4',
        ]), 'video')->create();
        $target = Storage::path('zips/archive-test/offers.zip');

        $cache = Mockery::mock(DownloadCacheService::class)->shouldIgnoreMissing();
        $csv = $this->app->make(CsvService::class);
        $packed = (new ZipService($cache, $csv))->buildArchive($target, $channel, collect([$good, $missing]), 'export-1');

        $this->assertSame([$good->id], $packed->modelKeys());
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($target) === true);
        $this->assertSame(2, $zip->numFiles);
        $this->assertSame('good-video', $zip->getFromName('good.mp4'));
        $this->assertSame($csv->buildInfoCsv(collect([$good])), $zip->getFromName('info.csv'));
        $zip->close();
        Storage::deleteDirectory('zips/archive-test');
    }

    public function testBuildArchiveLeavesNoFileWhenNothingCanBePacked(): void
    {
        $channel = Channel::factory()->create();
        $missing = Assignment::factory()->for($channel, 'channel')->for(Video::factory()->create([
            'disk' => 'local', 'path' => 'videos/missing.mp4', 'bytes' => 10,
        ]), 'video')->create();
        $target = Storage::path('zips/archive-test/empty.zip');

        $cache = Mockery::mock(DownloadCacheService::class)->shouldIgnoreMissing();
        try {
            (new ZipService($cache, $this->app->make(CsvService::class)))
                ->buildArchive($target, $channel, collect([$missing]), 'export-2');
            $this->fail('An archive without videos must not be built.');
        } catch (ZipEmptyException) {
            $this->assertFileDoesNotExist($target);
        }
        Storage::deleteDirectory('zips/archive-test');
    }
```

Add `use App\Exceptions\Zip\ZipEmptyException;` to the test imports.

- [ ] **Step 2: Run the tests to verify they fail**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --no-coverage --filter=testBuildArchive tests/Integration/Services/ZipServiceTest.php`
Expected: FAIL with `Call to undefined method App\Services\Zip\ZipService::buildArchive()`.

- [ ] **Step 3: Implement `buildArchive()` and let `build()` delegate**

In `ZipService`, replace the body of `build()` from `$tmpPath = $this->zipPath($jobId);` to the end of the method with:

```php
        $tmpPath = $this->zipPath($jobId);

        try {
            $this->cache->setStatus($jobId, DownloadStatusEnum::PREPARING->value);
            $this->cache->setProgress($jobId, 0);

            $packed = $this->buildArchive(Storage::path($tmpPath), $channel, $items, $jobId);

            // only packed offers are marked as downloaded once the archive is fetched
            $this->cache->setAssignments($jobId, $packed->pluck('id')->all());
            $this->cache->setFile($jobId, $tmpPath);
            $this->cache->setName($jobId, $downloadName);
            $this->cache->setProgress($jobId, 100);
            $this->cache->setStatus($jobId, DownloadStatusEnum::READY->value);
        } catch (\Throwable $e) {
            $this->cache->setStatus($jobId, DownloadStatusEnum::FAILED->value);
            throw $e;
        }

        return $tmpPath;
    }

    /**
     * Pack the offers into a ZIP at the given absolute path, skipping videos that cannot be delivered.
     * @param Collection<int, Assignment> $items
     * @return Collection<int, Assignment> The offers whose videos are in the archive.
     * @throws ZipEmptyException When no offered video could be packed.
     * @throws ZipBuildException When the archive cannot be written.
     */
    public function buildArchive(string $absolutePath, Channel $channel, Collection $items, string $jobId): Collection
    {
        if ($items->isEmpty()) {
            throw new ZipBuildException('No downloadable videos remain in this selection.');
        }

        $this->prepareDirectories();
        File::ensureDirectoryExists(dirname($absolutePath));
        $zip = $this->createZipArchive($absolutePath);
        $tmpFiles = [];

        try {
            $packed = $this->addAssignmentsToZip($zip, $jobId, $channel, $items, $tmpFiles);
            if ($packed->isEmpty()) {
                throw ZipEmptyException::allSkipped();
            }
            if (!$zip->addFromString('info.csv', $this->csvService->buildInfoCsv($packed))) {
                throw new ZipBuildException('Cannot add metadata to the ZIP archive.');
            }
            $this->cache->setStatus($jobId, DownloadStatusEnum::PACKING->value);
            if (!$zip->close()) {
                throw new ZipBuildException('The ZIP archive could not be finalized.');
            }

            return $packed;
        } catch (\Throwable $e) {
            // zip->close() must be called even on failure or libzip leaks file handles
            try {
                $zip->close();
            } catch (\Throwable) {
            }
            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
            throw $e;
        } finally {
            // always wipe any Dropbox tmp copies regardless of success or failure
            foreach ($tmpFiles as $file) {
                Storage::delete($file);
            }
        }
    }
```

Change `createZipArchive()` to take the absolute path:

```php
    private function createZipArchive(string $absolutePath): ZipArchive
    {
        $zip = new ZipArchive();
        if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new ZipBuildException('Cannot create the ZIP archive.');
        }

        return $zip;
    }
```

Delete the now unused `finalizeZip()` method and the `$this->prepareDirectories();` / `$zip = ...` / `$tmpFiles = [];` lines that preceded the old `try` in `build()`. Add `use Illuminate\Support\Facades\File;`.

- [ ] **Step 4: Run the ZIP suites**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --no-coverage tests/Integration/Services/ZipServiceTest.php` and the same for `tests/Feature/Http/Controllers/DownloadReliabilityTest.php`, `tests/Feature/Jobs/BuildZipJobTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Zip/ZipService.php tests/Integration/Services/ZipServiceTest.php
git commit -m "refactor(downloads): extract a reusable ZIP archive builder"
```

---

### Task 3: Export files, exporter, ZIP format and download route

**Files:**
- Create: `app/Services/OfferExportFileService.php`
- Create: `app/Filament/Standard/Exports/OfferExporter.php`
- Create: `app/Filament/Standard/Exports/OfferExportZipDownloader.php`
- Create: `app/Enum/OfferExportFormatEnum.php`
- Create: `app/Http/Controllers/OfferExportDownloadController.php`
- Modify: `routes/web.php` (next to the `zips.*` routes)
- Modify: `lang/de/my_offers.php`, `lang/en/my_offers.php` (new `export` group)
- Test: `tests/Feature/Http/Controllers/OfferExportDownloadControllerTest.php`, `tests/Integration/Filament/Standard/Exports/OfferExporterTest.php`

**Interfaces:**
- Produces:
  - `OfferExportFileService::zipPath(Export $export): string` (relative on the export disk: `{dir}/{file_name}.zip`), `absoluteZipPath(Export): string`, `writePackedIds(Export, array $ids): void`, `packedIds(Export): list<int>`, `hasZip(Export): bool`.
  - `OfferExportFormatEnum::Zip` implementing `Filament\Actions\Exports\Enums\Contracts\ExportFormat`.
  - Route `offers.exports.download` (`GET /offers/exports/{export}/download`, `signed:relative`, query `authGuard`).
  - `OfferExporter` with `options['channel_id']`.

- [ ] **Step 1: Write the failing controller test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enum\Guard\GuardEnum;
use App\Enum\OfferExportFormatEnum;
use App\Filament\Standard\Exports\OfferExporter;
use App\Models\Assignment;
use App\Models\User;
use App\Services\OfferExportFileService;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\DatabaseTestCase;

final class OfferExportDownloadControllerTest extends DatabaseTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->owner = User::factory()->standard()->create();
    }

    private function export(array $attributes = []): Export
    {
        $export = new Export();
        $export->forceFill([
            'exporter' => OfferExporter::class,
            'file_disk' => 'local',
            'file_name' => 'videos_test',
            'total_rows' => 2,
            'processed_rows' => 2,
            'successful_rows' => 1,
            'completed_at' => now(),
            ...$attributes,
        ]);
        $export->user()->associate($this->owner);
        $export->save();

        return $export;
    }

    private function url(Export $export): string
    {
        return URL::signedRoute('offers.exports.download', ['export' => $export, 'authGuard' => GuardEnum::STANDARD->value], absolute: false);
    }

    public function testOwnerReceivesTheZipAndOnlyPackedOffersAreMarkedDownloaded(): void
    {
        $packed = Assignment::factory()->create(['status' => 'notified']);
        $skipped = Assignment::factory()->create(['status' => 'notified']);
        $export = $this->export();
        $files = $this->app->make(OfferExportFileService::class);
        Storage::disk('local')->put($files->zipPath($export), 'zip-bytes');
        $files->writePackedIds($export, [$packed->id]);

        $response = $this->actingAs($this->owner, GuardEnum::STANDARD->value)->get($this->url($export))->assertOk();

        $this->assertSame('attachment; filename=videos_test.zip', $response->headers->get('content-disposition'));
        $this->assertDatabaseHas('assignments', ['id' => $packed->id, 'status' => 'picked_up']);
        $this->assertDatabaseHas('assignments', ['id' => $skipped->id, 'status' => 'notified']);
        $this->actingAs($this->owner, GuardEnum::STANDARD->value)->get($this->url($export))->assertOk();
    }

    public function testOtherUsersAndGuestsAreRejected(): void
    {
        $export = $this->export();
        Storage::disk('local')->put($this->app->make(OfferExportFileService::class)->zipPath($export), 'zip-bytes');

        $this->get($this->url($export))->assertUnauthorized();
        $this->actingAs(User::factory()->standard()->create(), GuardEnum::STANDARD->value)
            ->get($this->url($export))->assertForbidden();
        $this->actingAs($this->owner, GuardEnum::STANDARD->value)
            ->get('/offers/exports/'.$export->id.'/download?authGuard=standard')->assertForbidden();
    }

    public function testForeignUnfinishedOrMissingExportsAreNotFound(): void
    {
        $foreign = $this->export(['exporter' => 'App\\Other\\Exporter']);
        $unfinished = $this->export(['completed_at' => null]);
        $withoutFile = $this->export();
        $files = $this->app->make(OfferExportFileService::class);
        Storage::disk('local')->put($files->zipPath($foreign), 'zip-bytes');
        Storage::disk('local')->put($files->zipPath($unfinished), 'zip-bytes');

        foreach ([$foreign, $unfinished, $withoutFile] as $export) {
            $this->actingAs($this->owner, GuardEnum::STANDARD->value)->get($this->url($export))->assertNotFound();
        }
    }

    public function testNotificationButtonPointsToTheSignedDownload(): void
    {
        $export = $this->export();

        $action = OfferExportFormatEnum::Zip->getDownloadNotificationAction($export, GuardEnum::STANDARD->value);

        $this->assertSame(__('my_offers.export.download'), $action->getLabel());
        $this->assertSame($this->url($export), $action->getUrl());
    }
}
```

- [ ] **Step 2: Write the failing exporter test**

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Exports;

use App\Filament\Standard\Exports\OfferExporter;
use App\Models\Channel;
use Filament\Actions\Exports\Models\Export;
use Tests\DatabaseTestCase;

final class OfferExporterTest extends DatabaseTestCase
{
    public function testCompletionTextsNameReadyAndSkippedVideos(): void
    {
        $export = new Export();
        $export->forceFill(['total_rows' => 3, 'successful_rows' => 2]);

        $this->assertSame(__('my_offers.export.completed.title'), OfferExporter::getCompletedNotificationTitle($export));
        $this->assertSame(
            trans_choice('my_offers.export.completed.ready', 3, ['ready' => 2, 'total' => 3]).' '
                .trans_choice('my_offers.export.completed.skipped', 1, ['count' => 1]),
            OfferExporter::getCompletedNotificationBody($export),
        );

        $export->forceFill(['successful_rows' => 0]);
        $this->assertSame(__('my_offers.export.failed.title'), OfferExporter::getCompletedNotificationTitle($export));
        $this->assertSame(__('my_offers.export.failed.body'), OfferExporter::getCompletedNotificationBody($export));
    }

    public function testFileNameNamesTheChannelAndTheExport(): void
    {
        $channel = Channel::factory()->create(['name' => 'Road Rave Germany']);
        $export = new Export();
        $export->forceFill(['id' => 42]);

        $exporter = new OfferExporter($export, ['id' => 'ID'], ['channel_id' => $channel->id]);

        $this->assertMatchesRegularExpression('/^videos_road-rave-germany_\d{4}-\d{2}-\d{2}_42$/', $exporter->getFileName($export));
    }
}
```

- [ ] **Step 3: Run both tests to verify they fail**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --no-coverage tests/Feature/Http/Controllers/OfferExportDownloadControllerTest.php` and `.../tests/Integration/Filament/Standard/Exports/OfferExporterTest.php`
Expected: FAIL (classes and route not defined).

- [ ] **Step 4: Implement `OfferExportFileService`**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Filament\Actions\Exports\Models\Export;

/**
 * Knows where the ZIP and the list of packed offers of an offer export are stored.
 */
final class OfferExportFileService
{
    private const PACKED_FILE = 'packed.json';

    public function zipPath(Export $export): string
    {
        return $export->getFileDirectory().DIRECTORY_SEPARATOR.$export->file_name.'.zip';
    }

    public function absoluteZipPath(Export $export): string
    {
        return $export->getFileDisk()->path($this->zipPath($export));
    }

    public function hasZip(Export $export): bool
    {
        return $export->getFileDisk()->exists($this->zipPath($export));
    }

    /** @param list<int> $ids */
    public function writePackedIds(Export $export, array $ids): void
    {
        $export->getFileDisk()->put($this->packedPath($export), json_encode(array_values($ids), JSON_THROW_ON_ERROR));
    }

    /** @return list<int> */
    public function packedIds(Export $export): array
    {
        $json = $export->getFileDisk()->get($this->packedPath($export));

        return $json === null ? [] : array_map('intval', json_decode($json, true, flags: JSON_THROW_ON_ERROR));
    }

    private function packedPath(Export $export): string
    {
        return $export->getFileDirectory().DIRECTORY_SEPARATOR.self::PACKED_FILE;
    }
}
```

- [ ] **Step 5: Implement the downloader, format enum and exporter**

`app/Filament/Standard/Exports/OfferExportZipDownloader.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Standard\Exports;

use App\Services\OfferExportFileService;
use Filament\Actions\Exports\Downloaders\Contracts\Downloader;
use Filament\Actions\Exports\Models\Export;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Streams the ZIP of an offer export; range requests keep working for resumed downloads.
 */
final readonly class OfferExportZipDownloader implements Downloader
{
    public function __construct(private OfferExportFileService $files)
    {
    }

    public function __invoke(Export $export): BinaryFileResponse
    {
        return response()->download(
            $this->files->absoluteZipPath($export),
            $export->file_name.'.zip',
            ['Cache-Control' => 'private, no-store'],
        );
    }
}
```

`app/Enum/OfferExportFormatEnum.php`:

```php
<?php

declare(strict_types=1);

namespace App\Enum;

use App\Filament\Standard\Exports\OfferExportZipDownloader;
use Filament\Actions\Action;
use Filament\Actions\Exports\Downloaders\Contracts\Downloader;
use Filament\Actions\Exports\Enums\Contracts\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\URL;

/**
 * Offer exports are delivered as one ZIP with the videos and their info.csv.
 */
enum OfferExportFormatEnum: string implements ExportFormat
{
    case Zip = 'zip';

    public function getDownloader(): Downloader
    {
        return app(OfferExportZipDownloader::class);
    }

    public function getDownloadNotificationAction(Export $export, string $authGuard): Action
    {
        return Action::make('download_zip')
            ->label(__('my_offers.export.download'))
            ->url(URL::signedRoute('offers.exports.download', ['export' => $export, 'authGuard' => $authGuard], absolute: false), shouldOpenInNewTab: true)
            ->markAsRead();
    }
}
```

`app/Filament/Standard/Exports/OfferExporter.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Standard\Exports;

use App\Enum\OfferExportFormatEnum;
use App\Models\Assignment;
use App\Models\Channel;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Str;

/**
 * Exports offers as a ZIP with their videos; info.csv is written by the archive builder.
 */
class OfferExporter extends Exporter
{
    protected static ?string $model = Assignment::class;

    /** Filament needs at least one column; the archive content does not depend on them. */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('video.original_name')->label(__('my_offers.export.column_video')),
        ];
    }

    public function getFormats(): array
    {
        return [OfferExportFormatEnum::Zip];
    }

    public function getFileName(Export $export): string
    {
        $channel = Channel::query()->find($this->options['channel_id'] ?? null);

        return sprintf('videos_%s_%s_%s', Str::slug((string) ($channel?->name ?: 'offers')), now()->format('Y-m-d'), $export->getKey());
    }

    public static function getCompletedNotificationTitle(Export $export): string
    {
        return $export->successful_rows > 0
            ? __('my_offers.export.completed.title')
            : __('my_offers.export.failed.title');
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        if ($export->successful_rows === 0) {
            return __('my_offers.export.failed.body');
        }

        $body = trans_choice('my_offers.export.completed.ready', $export->total_rows, [
            'ready' => $export->successful_rows,
            'total' => $export->total_rows,
        ]);
        $skipped = $export->getFailedRowsCount();

        return $skipped > 0
            ? $body.' '.trans_choice('my_offers.export.completed.skipped', $skipped, ['count' => $skipped])
            : $body;
    }
}
```

- [ ] **Step 6: Implement the controller and route**

`app/Http/Controllers/OfferExportDownloadController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Standard\Exports\OfferExporter;
use App\Filament\Standard\Exports\OfferExportZipDownloader;
use App\Repository\AssignmentRepository;
use App\Services\AssignmentService;
use App\Services\OfferExportFileService;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Delivers the ZIP of an offer export to its creator and records the packed offers as downloaded.
 */
final class OfferExportDownloadController extends Controller
{
    public function __construct(
        private readonly OfferExportFileService $files,
        private readonly AssignmentRepository $assignmentRepository,
        private readonly AssignmentService $assignments,
        private readonly OfferExportZipDownloader $downloader,
    ) {
    }

    public function __invoke(Request $request, Export $export): BinaryFileResponse
    {
        $guard = (string) $request->query('authGuard');
        abort_unless(array_key_exists($guard, config('auth.guards')) && auth($guard)->check(), 401);
        abort_unless($export->user()->is(auth($guard)->user()), 403);
        abort_unless(
            $export->exporter === OfferExporter::class && $export->completed_at !== null && $this->files->hasZip($export),
            404,
        );

        foreach ($this->assignmentRepository->findByIds($this->files->packedIds($export)) as $assignment) {
            $this->assignments->markDownloaded($assignment, (string) $request->ip(), $request->userAgent());
        }

        return ($this->downloader)($export);
    }
}
```

In `routes/web.php`, directly after the `zips.download` route:

```php
    Route::get('/offers/exports/{export}/download', OfferExportDownloadController::class)
        ->middleware('signed:relative')
        ->name('offers.exports.download');
```

and `use App\Http\Controllers\OfferExportDownloadController;` at the top.

- [ ] **Step 7: Add the translations**

`lang/de/my_offers.php`, new top-level key:

```php
    'export' => [
        'column_video' => 'Video',
        'download' => 'ZIP herunterladen',
        'completed' => [
            'title' => 'Download bereit',
            'ready' => ':ready Video ist bereit.|:ready von :total Videos sind bereit.',
            'skipped' => ':count Video war nicht verfügbar und wurde übersprungen.|:count Videos waren nicht verfügbar und wurden übersprungen.',
        ],
        'failed' => [
            'title' => 'Download nicht möglich',
            'body' => 'Keines der ausgewählten Videos war verfügbar. Bitte versuche es später erneut.',
        ],
    ],
```

`lang/en/my_offers.php`:

```php
    'export' => [
        'column_video' => 'Video',
        'download' => 'Download ZIP',
        'completed' => [
            'title' => 'Download ready',
            'ready' => ':ready video is ready.|:ready of :total videos are ready.',
            'skipped' => ':count video was not available and was skipped.|:count videos were not available and were skipped.',
        ],
        'failed' => [
            'title' => 'Download not possible',
            'body' => 'None of the selected videos was available. Please try again later.',
        ],
    ],
```

- [ ] **Step 8: Run the tests**

Run: the two test files from Step 3.
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Services/OfferExportFileService.php app/Filament/Standard/Exports app/Enum/OfferExportFormatEnum.php app/Http/Controllers/OfferExportDownloadController.php routes/web.php lang/de/my_offers.php lang/en/my_offers.php tests/Feature/Http/Controllers/OfferExportDownloadControllerTest.php tests/Integration/Filament/Standard/Exports/OfferExporterTest.php
git commit -m "feat(downloads): deliver offer exports as ZIP through a signed download"
```

---

### Task 4: `BuildOfferExportZipJob`

**Files:**
- Create: `app/Jobs/BuildOfferExportZipJob.php`
- Test: `tests/Integration/Jobs/BuildOfferExportZipJobTest.php`

**Interfaces:**
- Consumes: `ZipService::buildArchive()` (Task 2), `OfferExportFileService` (Task 3), `AssignmentRepository::fetchDownloadableForChannel(Channel, Collection, ?Batch)`, `ChannelRepository::findById(int)`.
- Produces: job with Filament's preparation-job constructor `(Export $export, string $query, array $columnMap, array $options = [], int $chunkSize = 100, ?array $records = null)`; sets `total_rows`, `processed_rows`, `successful_rows`; writes the ZIP and `packed.json`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Jobs;

use AnourValar\EloquentSerialize\Facades\EloquentSerializeFacade;
use App\Filament\Standard\Exports\OfferExporter;
use App\Jobs\BuildOfferExportZipJob;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\User;
use App\Models\Video;
use App\Services\CsvService;
use App\Services\OfferExportFileService;
use Filament\Actions\Exports\Jobs\ExportCompletion;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\DatabaseTestCase;
use ZipArchive;

final class BuildOfferExportZipJobTest extends DatabaseTestCase
{
    private string $root;

    private Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/offer-exports-'.Str::uuid());
        config()->set('filesystems.default', 'local');
        config()->set('filesystems.disks.local.root', $this->root);
        Storage::forgetDisk('local');
        $this->channel = Channel::factory()->create(['name' => 'Road Rave']);
    }

    protected function tearDown(): void
    {
        Storage::disk('local')->deleteDirectory('');
        parent::tearDown();
    }

    private function offer(string $name, bool $withFile = true, array $attributes = []): Assignment
    {
        $path = 'videos/'.Str::uuid().'.mp4';
        if ($withFile) {
            Storage::put($path, $name.'-content');
        }

        return Assignment::factory()->forChannel($this->channel)->forVideo(Video::factory()->create([
            'disk' => 'local', 'path' => $path, 'original_name' => $name, 'bytes' => 13,
        ]))->create(['status' => 'notified', ...$attributes]);
    }

    private function export(int $rows): Export
    {
        $export = new Export();
        $export->forceFill(['exporter' => OfferExporter::class, 'file_disk' => 'local', 'total_rows' => $rows]);
        $export->user()->associate(User::factory()->standard()->create());
        $export->save();
        $export->forceFill(['file_name' => 'videos_test'])->save();

        return $export;
    }

    /** @param list<Assignment> $offers */
    private function runJob(Export $export, array $offers): void
    {
        $job = new BuildOfferExportZipJob(
            export: $export,
            query: EloquentSerializeFacade::serialize(Assignment::query()),
            columnMap: ['id' => 'ID'],
            options: ['channel_id' => $this->channel->id],
            records: $offers,
        );
        $this->app->call([$job, 'handle']);
        $export->refresh();
    }

    public function testSelectedOffersArePackedWithTheirInfoCsv(): void
    {
        $first = $this->offer('first.mp4');
        $second = $this->offer('second.mp4');
        $export = $this->export(2);

        $this->runJob($export, [$first, $second]);

        $files = $this->app->make(OfferExportFileService::class);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($files->absoluteZipPath($export)) === true);
        $this->assertSame('first.mp4-content', $zip->getFromName('first.mp4'));
        $this->assertSame('second.mp4-content', $zip->getFromName('second.mp4'));
        $this->assertSame(
            $this->app->make(CsvService::class)->buildInfoCsv(Assignment::with('video.clips')->findMany([$first->id, $second->id])),
            $zip->getFromName('info.csv'),
        );
        $zip->close();
        $this->assertSame([2, 2, 2], [$export->total_rows, $export->processed_rows, $export->successful_rows]);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $files->packedIds($export));
    }

    public function testMissingAndExpiredOffersAreSkippedAndLogged(): void
    {
        Log::spy();
        $good = $this->offer('good.mp4');
        $missing = $this->offer('missing.mp4', withFile: false);
        $expired = $this->offer('expired.mp4', attributes: ['expires_at' => now()->subMinute()]);
        $export = $this->export(3);

        $this->runJob($export, [$good, $missing, $expired]);

        $this->assertSame([3, 1], [$export->total_rows, $export->successful_rows]);
        $this->assertSame([$good->id], $this->app->make(OfferExportFileService::class)->packedIds($export));
        $reasons = [];
        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context = []) use (&$reasons): bool {
            if (str_starts_with($message, 'Offered video')) {
                $reasons[$context['assignment_id']] = $context['reason'];
            }
            return true;
        });
        $this->assertSame([$missing->id => 'file_missing', $expired->id => 'offer_unavailable'], $this->sorted($reasons));
    }

    public function testNothingPackableLeavesNoArchiveAndNoDownloadButton(): void
    {
        $missing = $this->offer('missing.mp4', withFile: false);
        $export = $this->export(1);

        $this->runJob($export, [$missing]);

        $this->assertSame(0, $export->successful_rows);
        $this->assertFalse($this->app->make(OfferExportFileService::class)->hasZip($export));
    }

    public function testCompletionNotificationCarriesTheDownloadButtonAndSkippedCount(): void
    {
        config()->set('broadcasting.default', 'null');
        $good = $this->offer('good.mp4');
        $missing = $this->offer('missing.mp4', withFile: false);
        $export = $this->export(2);
        $this->runJob($export, [$good, $missing]);

        $completion = app(ExportCompletion::class, [
            'export' => $export,
            'columnMap' => ['id' => 'ID'],
            'formats' => (new OfferExporter($export, ['id' => 'ID'], []))->getFormats(),
            'options' => [],
            'authGuard' => 'standard',
        ]);
        $completion->connection = 'redis';
        $completion->handle();

        $notification = $export->user->notifications()->sole();
        $this->assertSame(__('my_offers.export.completed.title'), $notification->data['title']);
        $this->assertStringContainsString(trans_choice('my_offers.export.completed.skipped', 1, ['count' => 1]), $notification->data['body']);
        $this->assertStringContainsString('/offers/exports/'.$export->id.'/download', $notification->data['actions'][0]['url']);
    }

    public function testFailedBuildLogsEveryOfferOnce(): void
    {
        Log::spy();
        $first = $this->offer('first.mp4');
        $second = $this->offer('second.mp4');
        $export = $this->export(2);
        $job = new BuildOfferExportZipJob(
            export: $export,
            query: EloquentSerializeFacade::serialize(Assignment::query()->whereKey([$first->id, $second->id])),
            columnMap: ['id' => 'ID'],
            options: ['channel_id' => $this->channel->id],
            records: null,
        );

        $job->failed(new RuntimeException('disk full'));

        $contexts = [];
        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context = []) use (&$contexts): bool {
            $contexts[] = $context;
            return true;
        });
        $this->assertSame(['zip_failed', 'zip_failed'], array_column($contexts, 'reason'));
        $this->assertSame([$first->id, $second->id], array_column($contexts, 'assignment_id'));
    }

    /** @param array<int, string> $values @return array<int, string> */
    private function sorted(array $values): array
    {
        ksort($values);

        return $values;
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --no-coverage tests/Integration/Jobs/BuildOfferExportZipJobTest.php`
Expected: FAIL with `Class "App\Jobs\BuildOfferExportZipJob" not found`.

- [ ] **Step 3: Implement the job**

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use AnourValar\EloquentSerialize\Facades\EloquentSerializeFacade;
use App\Exceptions\Zip\ZipEmptyException;
use App\Repository\AssignmentRepository;
use App\Repository\ChannelRepository;
use App\Services\OfferExportFileService;
use App\Services\Zip\ZipService;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Builds the ZIP of an offer export in place of Filament's CSV preparation job.
 */
class BuildOfferExportZipJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public bool $deleteWhenMissingModels = true;

    public ?int $tries = 1;

    public int $timeout = 3600;

    /**
     * Same signature as Filament's preparation job, which the export action instantiates.
     * @param array<string, string> $columnMap
     * @param array<string, mixed> $options
     * @param array<Model>|null $records
     */
    public function __construct(
        protected Export $export,
        protected string $query,
        protected array $columnMap,
        protected array $options = [],
        protected int $chunkSize = 100,
        protected ?array $records = null,
    ) {
    }

    public function handle(
        ZipService $zips,
        AssignmentRepository $assignments,
        ChannelRepository $channels,
        OfferExportFileService $files,
    ): void {
        $requested = $this->requestedIds();
        $channel = $channels->findById((int) ($this->options['channel_id'] ?? 0));
        $items = $channel !== null
            ? $assignments->fetchDownloadableForChannel($channel, $requested)
            : collect();
        $packed = collect();

        foreach ($requested->diff($items->modelKeys()) as $unavailableId) {
            Log::warning('Offered video skipped in ZIP download', [
                'reason' => 'offer_unavailable',
                'job_id' => $this->jobId(),
                'assignment_id' => $unavailableId,
                'channel_id' => $channel?->getKey(),
            ]);
        }

        try {
            if ($channel !== null && $items->isNotEmpty()) {
                $packed = $zips->buildArchive($files->absoluteZipPath($this->export), $channel, $items, $this->jobId());
                $files->writePackedIds($this->export, $packed->modelKeys());
            }
        } catch (ZipEmptyException) {
            // every skipped offer was already logged one by one
        } finally {
            $this->export->forceFill([
                'total_rows' => $requested->count(),
                'processed_rows' => $requested->count(),
                'successful_rows' => $packed->count(),
            ])->save();
        }
    }

    /** Log every selected offer that could not be delivered because the build failed. */
    public function failed(?Throwable $exception): void
    {
        foreach ($this->requestedIds() as $assignmentId) {
            Log::warning('Offered video not delivered because the ZIP download failed', [
                'reason' => 'zip_failed',
                'message' => $exception?->getMessage(),
                'job_id' => $this->jobId(),
                'assignment_id' => $assignmentId,
                'channel_id' => $this->options['channel_id'] ?? null,
            ]);
        }
    }

    /** @return Collection<int, int> */
    private function requestedIds(): Collection
    {
        if ($this->records !== null) {
            return collect($this->records)->map(fn (Model $record): int => (int) $record->getKey())->values();
        }

        $query = EloquentSerializeFacade::unserialize($this->query);

        return $query->pluck($query->getModel()->getQualifiedKeyName())->map(fn ($id): int => (int) $id)->values();
    }

    private function jobId(): string
    {
        return 'export-'.$this->export->getKey();
    }
}
```

- [ ] **Step 4: Run the tests**

Run: the command from Step 2.
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/BuildOfferExportZipJob.php tests/Integration/Jobs/BuildOfferExportZipJobTest.php
git commit -m "feat(downloads): build offer export archives in a queued job"
```

---

### Task 5: Export actions on "Meine Angebote" and removal of the dialog wiring

**Files:**
- Create: `app/Filament/Standard/Pages/MyOffers/Table/OfferExportActionConfigurator.php`
- Modify: `app/Filament/Standard/Pages/MyOffers/Table/BulkActions.php` (`downloadSelected()`)
- Modify: `app/Filament/Standard/Pages/MyOffers/Table/Actions.php` (`download()`, `downloadAgain()`)
- Modify: `app/Filament/Standard/Pages/MyOffers.php` (remove zip anchor and `dispatchZipDownload()`)
- Delete: `app/Application/Offer/DispatchZipDownload.php`, `resources/views/filament/standard/components/zip-form-anchor.blade.php`
- Modify: `resources/js/app.js` (remove zip wiring), `routes/web.php` (remove `zips.channel.start`), `app/Http/Controllers/ZipController.php` (remove `startForChannel()`), `app/Services/LinkService.php` (remove `getZipSelectedUrlForChannel()`)
- Create: `lang/vendor/filament-actions/de/export.php`, `lang/vendor/filament-actions/en/export.php`
- Modify tests: `tests/Feature/Standard/Pages/MyOffersTest.php`, `tests/Feature/Http/Controllers/ZipControllerTest.php`, `tests/Feature/Http/Controllers/DownloadReliabilityTest.php`, `tests/Unit/Services/LinkServiceTest.php`
- Rebuild: `public/build`

**Interfaces:**
- Consumes: `OfferExporter`, `BuildOfferExportZipJob`, `OfferExportFormatEnum`, `GetCurrentChannel::handle(): ?Channel`.
- Produces: `OfferExportActionConfigurator::configure(ExportAction|ExportBulkAction $action): ExportAction|ExportBulkAction`.

- [ ] **Step 1: Write the failing page tests** (replace `testZipFormAnchorIsRenderedWhenChannelExists`, `testBulkDownloadDispatchesZipEvent`, `testMergeComponentsAddsZipAnchorWhenChannelExists`, `testMergeComponentsKeepsComponentsWhenChannelMissing` in `MyOffersTest` with)

```php
    private function operatorWithOffer(string $status = 'notified'): array
    {
        $user = User::factory()->create();
        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles([RoleEnum::CHANNEL_OPERATOR->value]);
        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user, ['is_user_verified' => true]);
        $assignment = Assignment::factory()->withBatch()->create([
            'channel_id' => $channel->getKey(), 'status' => $status, 'expires_at' => now()->addDays(3),
        ]);
        Filament::setTenant($team, true);
        Filament::auth()->login($user);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        return [$user, $channel, $assignment];
    }

    public function testBulkDownloadStartsAnOfferExportForTheSelection(): void
    {
        Bus::fake();
        [$user, $channel, $assignment] = $this->operatorWithOffer();

        Livewire::test(MyOffers::class, ['activeTab' => 'available'])
            ->loadTable()
            ->assertTableBulkActionVisible('download_selected')
            ->callTableBulkAction('download_selected', [$assignment])
            ->assertHasNoTableBulkActionErrors();

        $export = Export::query()->sole();
        $this->assertSame(OfferExporter::class, $export->exporter);
        $this->assertTrue($export->user->is($user));
        $this->assertSame(1, $export->total_rows);
    }

    public function testRowDownloadExportsExactlyItsOffer(): void
    {
        Bus::fake();
        [, , $assignment] = $this->operatorWithOffer();
        Assignment::factory()->withBatch()->create(['channel_id' => $assignment->channel_id, 'status' => 'notified']);

        Livewire::test(MyOffers::class, ['activeTab' => 'available'])
            ->loadTable()
            ->callTableAction('download', $assignment);

        $this->assertSame(1, Export::query()->sole()->total_rows);
    }

    public function testDownloadAgainIsAvailableForDownloadedOffers(): void
    {
        Bus::fake();
        [, , $assignment] = $this->operatorWithOffer('picked_up');
        Download::factory()->create(['assignment_id' => $assignment->getKey(), 'downloaded_at' => now()]);

        Livewire::test(MyOffers::class, ['activeTab' => 'downloaded'])
            ->loadTable()
            ->assertTableActionVisible('download_again', $assignment)
            ->callTableAction('download_again', $assignment);

        $this->assertSame(1, Export::query()->sole()->total_rows);
    }
```

Imports to add: `App\Filament\Standard\Exports\OfferExporter`, `Filament\Actions\Exports\Models\Export`, `Illuminate\Support\Facades\Bus`. Remove imports that become unused.

- [ ] **Step 2: Run to verify they fail**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --no-coverage tests/Feature/Standard/Pages/MyOffersTest.php`
Expected: the three new tests FAIL (no export is created).

- [ ] **Step 3: Implement the shared action configuration**

`app/Filament/Standard/Pages/MyOffers/Table/OfferExportActionConfigurator.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\MyOffers\Table;

use App\Application\Channel\GetCurrentChannel;
use App\Enum\OfferExportFormatEnum;
use App\Filament\Standard\Exports\OfferExporter;
use App\Jobs\BuildOfferExportZipJob;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;

/**
 * Configures the offer export actions so bulk and row downloads build the same ZIP.
 */
final readonly class OfferExportActionConfigurator
{
    public function __construct(private GetCurrentChannel $currentChannel)
    {
    }

    public function configure(ExportAction|ExportBulkAction $action): ExportAction|ExportBulkAction
    {
        return $action
            ->exporter(OfferExporter::class)
            ->job(BuildOfferExportZipJob::class)
            ->formats([OfferExportFormatEnum::Zip])
            ->columnMapping(false)
            ->maxRows(500)
            ->options(fn (): array => ['channel_id' => $this->currentChannel->handle()?->getKey()]);
    }
}
```

- [ ] **Step 4: Use it in the bulk and row actions**

`BulkActions::downloadSelected()`:

```php
    public function downloadSelected(MyOffers $page): ExportBulkAction
    {
        return app(OfferExportActionConfigurator::class)->configure(ExportBulkAction::make('download_selected'))
            ->label(__('my_offers.table.bulk_actions.download_selected'))
            ->icon('heroicon-m-arrow-down-tray')
            ->color('primary')
            ->visible(fn (): bool => $page->activeTab === 'available');
    }
```

`Actions::download()` and `Actions::downloadAgain()`:

```php
    public function downloadAgain(MyOffers $page): ExportAction
    {
        return $this->exportRecord(ExportAction::make('download_again'))
            ->label(__('my_offers.table.actions.download_again'))
            ->icon('heroicon-m-arrow-path')
            ->color('gray')
            ->visible(fn (): bool => $page->activeTab === 'downloaded');
    }

    public function download(MyOffers $page): ExportAction
    {
        return $this->exportRecord(ExportAction::make('download'))
            ->label(__('my_offers.table.actions.download'))
            ->icon('heroicon-m-arrow-down-tray')
            ->color('primary')
            ->visible(fn (): bool => $page->activeTab === 'available');
    }

    private function exportRecord(ExportAction $action): ExportAction
    {
        return app(OfferExportActionConfigurator::class)->configure($action)
            ->modifyQueryUsing(fn (Builder $query, Assignment $record): Builder => $query->whereKey($record->getKey()));
    }
```

Adjust imports (`Filament\Actions\ExportAction`, `Filament\Actions\ExportBulkAction`, `Illuminate\Database\Eloquent\Builder`) and the `make()` return types (`array<int, Action|ExportAction>`).

- [ ] **Step 5: Replace Filament's start notification texts**

`lang/vendor/filament-actions/de/export.php`:

```php
<?php

return [
    'notifications' => [
        'started' => [
            'title' => 'Download wird vorbereitet',
            'body' => '1 Video wird gepackt. Sie erhalten eine Benachrichtigung, sobald die ZIP-Datei bereit ist.|:count Videos werden gepackt. Sie erhalten eine Benachrichtigung, sobald die ZIP-Datei bereit ist.',
        ],
    ],
];
```

`lang/vendor/filament-actions/en/export.php`:

```php
<?php

return [
    'notifications' => [
        'started' => [
            'title' => 'Preparing your download',
            'body' => '1 video is being packed. You will be notified as soon as the ZIP file is ready.|:count videos are being packed. You will be notified as soon as the ZIP file is ready.',
        ],
    ],
];
```

- [ ] **Step 6: Remove the dialog wiring**

- `MyOffers`: delete `mergeComponentsIfChannelExists()` (return the components directly in `content()`), `dispatchZipDownload()` and the imports `DispatchZipDownload`, `LinkService`, `ViewField` if unused.
- Delete `app/Application/Offer/DispatchZipDownload.php` and `resources/views/filament/standard/components/zip-form-anchor.blade.php`.
- `resources/js/app.js`: remove the `ZipDownloader` import, the `downloaders` map, `downloaderFor()`, the `DOMContentLoaded` zip block and the `livewire:init` block (the public page initializes `ZipDownloader` in `resources/js/offers.js`).
- `routes/web.php`: remove the `zips.channel.start` route.
- `ZipController`: remove `startForChannel()`.
- `LinkService`: remove `getZipSelectedUrlForChannel()`.

- [ ] **Step 7: Move the remaining tests to the public page route**

- `ZipControllerTest`: delete `testStartForChannelDispatchesZipJobAndInitializesCache` and `testStartForChannelReturnsErrorWhenAssignmentsAreMissing`.
- `LinkServiceTest`: delete the test for `getZipSelectedUrlForChannel()`.
- `DownloadReliabilityTest`: add `private Batch $batch;` created in `setUp()` (`$this->batch = Batch::factory()->create();`), create offers with `->withBatch($this->batch)` in `offer()`, and replace `startUrl()` with

```php
    private function startUrl(Channel $channel): string
    {
        return URL::temporarySignedRoute('zips.start', now()->addHour(), ['batch' => $this->batch->id, 'channel' => $channel->id]);
    }
```

and in `testDownloadLinksRejectTamperingAndExpiredOffers` replace `'/zips/channel/'.$channel->id` with `'/zips/'.$this->batch->id.'/'.$channel->id`. Add `use App\Models\Batch;`.

- [ ] **Step 8: Rebuild the frontend**

Run: `npm run build`
Then verify: `grep -c "zip-download" $(python3 -c "import json;print('public/build/'+json.load(open('public/build/manifest.json'))['resources/js/app.js']['file'])")` prints `0`.

- [ ] **Step 9: Run the affected suites**

Run for each: `tests/Feature/Standard/Pages/MyOffersTest.php`, `tests/Feature/Http/Controllers/ZipControllerTest.php`, `tests/Feature/Http/Controllers/DownloadReliabilityTest.php`, `tests/Unit/Services/LinkServiceTest.php`, `tests/Feature/Http/Controllers/OfferControllerTest.php`
Expected: PASS.

- [ ] **Step 10: Commit**

```bash
git add -A app/Filament/Standard/Pages app/Application/Offer resources/views/filament/standard/components resources/js/app.js routes/web.php app/Http/Controllers/ZipController.php app/Services/LinkService.php lang/vendor public/build tests
git commit -m "feat(downloads): download offers through export notifications on my offers"
```

---

### Task 6: Prune offer exports

**Files:**
- Modify: `app/Console/Commands/CleanExpiredZipsCommand.php`
- Test: `tests/Integration/Commands/CleanExpiredZipsCommandTest.php` (create if missing; otherwise extend)

**Interfaces:**
- Consumes: `Export::deleteFileDirectory()`.

- [ ] **Step 1: Write the failing test**

```php
    public function testOfferExportsOlderThanADayAreDeletedWithTheirFiles(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $make = function (string $exporter, $createdAt) use ($user): Export {
            $export = new Export();
            $export->forceFill(['exporter' => $exporter, 'file_disk' => 'local', 'file_name' => 'x', 'total_rows' => 1]);
            $export->user()->associate($user);
            $export->created_at = $createdAt;
            $export->save();
            Storage::disk('local')->put($export->getFileDirectory().'/x.zip', 'zip');

            return $export;
        };
        $old = $make(OfferExporter::class, now()->subHours(25));
        $fresh = $make(OfferExporter::class, now()->subHours(2));
        $foreign = $make('App\\Other\\Exporter', now()->subDays(3));

        $this->artisan('zips:clean-expired')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertFalse(Storage::disk('local')->directoryExists($old->getFileDirectory()));
        $this->assertModelExists($fresh);
        $this->assertModelExists($foreign);
        $this->assertTrue(Storage::disk('local')->exists($fresh->getFileDirectory().'/x.zip'));
    }
```

- [ ] **Step 2: Run to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --no-coverage tests/Integration/Commands/CleanExpiredZipsCommandTest.php`
Expected: FAIL (`$old` still exists).

- [ ] **Step 3: Implement**

At the end of `handle()` before `return self::SUCCESS;`:

```php
        Export::query()
            ->where('exporter', OfferExporter::class)
            ->where('created_at', '<', now()->subDay())
            ->each(function (Export $export): void {
                $export->deleteFileDirectory();
                $export->delete();
            });
```

Update the `$description` to `'Remove ZIP archives, temporary copies and offer exports that are no longer offered for download'`, add the imports `App\Filament\Standard\Exports\OfferExporter` and `Filament\Actions\Exports\Models\Export`.

- [ ] **Step 4: Run the test**

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/CleanExpiredZipsCommand.php tests/Integration/Commands/CleanExpiredZipsCommandTest.php
git commit -m "feat(downloads): prune offer exports after a day"
```

---

### Task 7: Changelog, verification and pull request

**Files:**
- Modify: `CHANGELOG.md` (`## [Unreleased]` → `### Changed`)
- Modify: `docs/superpowers/specs/2026-09-19-offer-download-export-design.md` (status line)

- [ ] **Step 1: Changelog** (re-read `[Unreleased]` first; append to `### Changed`, create it in Keep a Changelog order if missing)

```markdown
- **Offer downloads**: Downloads on "Meine Angebote" are prepared in the background and delivered
  through a notification with a "Download ZIP" button instead of the progress dialog. The ZIP still
  contains the selected videos and their `info.csv`; unavailable videos are skipped and named in the
  notification. Prepared downloads are kept for one day.
```

- [ ] **Step 2: Spec status** → `Status: approved and implemented on 2026-09-19.`

- [ ] **Step 3: Run all touched suites plus Pint**

Run each: `MyOffersTest`, `OfferExportDownloadControllerTest`, `OfferExporterTest`, `BuildOfferExportZipJobTest`, `ZipServiceTest`, `DownloadReliabilityTest`, `ZipControllerTest`, `ZipDownloadTest`, `BuildZipJobTest`, `OfferControllerTest`, `CleanExpiredZipsCommandTest`, `LinkServiceTest`, then `docker exec dashclip-delivery-sharing-1 vendor/bin/pint --test <changed php files>`.
Expected: all PASS; Pint only reports findings that existed before in untouched lines.

- [ ] **Step 4: Real-stack check** (Horizon running): run `php artisan migrate`, trigger one export for a real offer through `BuildOfferExportZipJob` with `dispatchSync` from tinker, confirm the ZIP and `info.csv` in the export directory, then delete that export.

- [ ] **Step 5: Commit, push, PR against `4.x-dev`**

```bash
git add CHANGELOG.md docs/superpowers/specs/2026-09-19-offer-download-export-design.md
git commit -m "docs(downloads): record offer downloads through export notifications"
git push
gh pr create --base 4.x-dev --title "feat(downloads): download offers through export notifications"
```
