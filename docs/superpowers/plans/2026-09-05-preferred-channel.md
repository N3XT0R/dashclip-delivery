# Preferred Channel (`info.csv`) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let submitters put an optional `preferred_channel` column in `info.csv` so a video is
assigned to that channel when possible, without overriding quotas, blocks or team scope.

**Architecture:** The importer resolves the raw value (channel ID or case-insensitive name) to a
channel at import time and stores both the raw string and the resolved FK on `clips`. A dedicated
`PreferredChannelService` collapses per-clip wishes to a per-video and per-group channel. The
distributor tries the wished channel first (reusing the existing quota/block acceptance rules); if
the channel is valid but temporarily blocked/out of quota the group is deferred to the next run;
otherwise it falls through to the normal weighted round-robin. Assignments made via a wish are
flagged with `via_preferred_channel` and surfaced in the admin overview.

**Tech Stack:** PHP 8.3, Laravel 11, Filament 4, PHPUnit (parallel), MySQL. Tests run in Docker:
`docker exec dashclip-delivery-sharing-1 php artisan test --parallel`
(targeted: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=<name>`).

**Spec:** `docs/superpowers/specs/2026-09-05-preferred-channel-design.md`

## Global Constraints

- All new PHP files start with `declare(strict_types=1);`.
- ADR 0002: service class name is `App\Services\PreferredChannelService` (`*Service` suffix).
- ADR 0003: resolution/collapse logic lives in `PreferredChannelService`, not in `InfoImporter`
  or `AssignmentDistributor`; data access goes through repositories, not new inline
  `Model::query()` calls in services/importers.
- ADR 0004: throw no new exceptions — an invalid/unknown `preferred_channel` is an expected state
  (fallback + warning), not an error.
- ADR 0005: every new method gets method-level PHPDoc when behaviour is not obvious from the
  signature (resolution rules, conflict handling, return semantics).
- ADR 0001: no PHPUnit mocks in new tests — use real Eloquent models and the seeded test DB
  (`Tests\DatabaseTestCase`). Existing mock-based stub tests
  (`FakeDistributorDependencies`) may keep their style.
- ADR 0006: `CHANGELOG.md` entry under `## [Unreleased]`, English, lines ≤ 120 chars.
- ADR 0007: every commit message references `#139`.
- CSV stays **positional** (no header-based parser rewrite). New column is appended last.
- No quota override, no expiry/TTL special-casing for wished assignments.

---

### Task 1: Database schema + model changes

**Files:**
- Create: `database/migrations/2026_09_05_120000_add_preferred_channel_to_clips_table.php`
- Create: `database/migrations/2026_09_05_120100_add_via_preferred_channel_to_assignments_table.php`
- Modify: `app/Models/Clip.php` (`$fillable`, new `preferredChannel()` relation)
- Modify: `app/Models/Assignment.php` (`$fillable`, `$casts`, `getActivitylogOptions()`)
- Test: `tests/Integration/Models/PreferredChannelSchemaTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `clips.preferred_channel` — `string|null` (raw CSV value).
  - `clips.preferred_channel_id` — `int|null` FK → `channels.id`, `nullOnDelete`.
  - `Clip::preferredChannel(): BelongsTo` (relation name `preferredChannel`).
  - `assignments.via_preferred_channel` — `bool`, default `false`, cast `'boolean'`.

- [ ] **Step 1: Write the failing test**

`tests/Integration/Models/PreferredChannelSchemaTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\Video;
use Tests\DatabaseTestCase;

class PreferredChannelSchemaTest extends DatabaseTestCase
{
    public function testClipStoresRawAndResolvedPreferredChannel(): void
    {
        $channel = Channel::factory()->create();
        $video = Video::factory()->create();

        $clip = Clip::factory()->for($video)->create([
            'preferred_channel' => 'Highway West',
            'preferred_channel_id' => $channel->getKey(),
        ]);

        $clip->refresh();

        $this->assertSame('Highway West', $clip->preferred_channel);
        $this->assertSame($channel->getKey(), $clip->preferredChannel->getKey());
    }

    public function testAssignmentFlagsPreferredChannelAndCastsBoolean(): void
    {
        $channel = Channel::factory()->create();
        $video = Video::factory()->create();

        $assignment = Assignment::query()->create([
            'video_id' => $video->getKey(),
            'channel_id' => $channel->getKey(),
            'batch_id' => \App\Models\Batch::factory()->create()->getKey(),
            'status' => 'queued',
            'via_preferred_channel' => true,
        ]);

        $assignment->refresh();

        $this->assertTrue($assignment->via_preferred_channel);
    }

    public function testAssignmentDefaultsViaPreferredChannelToFalse(): void
    {
        $channel = Channel::factory()->create();
        $video = Video::factory()->create();

        $assignment = Assignment::query()->create([
            'video_id' => $video->getKey(),
            'channel_id' => $channel->getKey(),
            'batch_id' => \App\Models\Batch::factory()->create()->getKey(),
            'status' => 'queued',
        ]);

        $assignment->refresh();

        $this->assertFalse($assignment->via_preferred_channel);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=PreferredChannelSchemaTest`
Expected: FAIL — unknown column `preferred_channel` / `via_preferred_channel`.

- [ ] **Step 3: Write the clips migration**

`database/migrations/2026_09_05_120000_add_preferred_channel_to_clips_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clips', static function (Blueprint $table): void {
            // Raw value from info.csv, regardless of validity — for traceability/debugging.
            $table->string('preferred_channel')->nullable()->after('user_id');
            // Channel resolved at import time; only set when it exists AND is not paused.
            $table->foreignId('preferred_channel_id')
                ->nullable()
                ->after('preferred_channel')
                ->constrained('channels')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clips', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('preferred_channel_id');
            $table->dropColumn('preferred_channel');
        });
    }
};
```

- [ ] **Step 4: Write the assignments migration**

`database/migrations/2026_09_05_120100_add_via_preferred_channel_to_assignments_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assignments', static function (Blueprint $table): void {
            $table->boolean('via_preferred_channel')->default(false)->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', static function (Blueprint $table): void {
            $table->dropColumn('via_preferred_channel');
        });
    }
};
```

- [ ] **Step 5: Update `app/Models/Clip.php`**

Add to `$fillable` (after `'user_id'`):

```php
        'preferred_channel',
        'preferred_channel_id',
```

Add relation (near the existing `video()` / `user()` relations):

```php
    /**
     * The channel the submitter asked this clip's video to be assigned to.
     * Null when no preference was given or the raw value could not be resolved.
     */
    public function preferredChannel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'preferred_channel_id');
    }
```

Ensure `use App\Models\Channel;` is present (or reference `Channel::class` via existing import;
`Clip` is in `App\Models`, so `Channel::class` resolves without an import).

- [ ] **Step 6: Update `app/Models/Assignment.php`**

Add `'via_preferred_channel'` to `$fillable` (after `'note'`).
Add to `$casts`:

```php
        'via_preferred_channel' => 'boolean',
```

Add `'via_preferred_channel'` to the `logOnly([...])` array in `getActivitylogOptions()`.

- [ ] **Step 7: Run test to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=PreferredChannelSchemaTest`
Expected: PASS (3 tests).

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_09_05_120000_add_preferred_channel_to_clips_table.php \
        database/migrations/2026_09_05_120100_add_via_preferred_channel_to_assignments_table.php \
        app/Models/Clip.php app/Models/Assignment.php \
        tests/Integration/Models/PreferredChannelSchemaTest.php
git commit -m "feat(#139): add preferred_channel columns to clips and assignments"
```

---

### Task 2: `ChannelRepository::findByNameInsensitive`

**Files:**
- Modify: `app/Repository/ChannelRepository.php` (add method near existing `findByName()` ~line 246)
- Test: `tests/Integration/Repository/ChannelRepositoryTest.php` (create if absent, else extend)

**Interfaces:**
- Consumes: nothing.
- Produces: `ChannelRepository::findByNameInsensitive(string $name): ?Channel` — trims and
  lower-cases both sides; returns the first match or `null`.

- [ ] **Step 1: Write the failing test**

Create `tests/Integration/Repository/ChannelRepositoryTest.php` (or add these methods if the file
exists):

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Models\Channel;
use App\Repository\ChannelRepository;
use Tests\DatabaseTestCase;

class ChannelRepositoryTest extends DatabaseTestCase
{
    private ChannelRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(ChannelRepository::class);
    }

    public function testFindByNameInsensitiveMatchesRegardlessOfCaseAndWhitespace(): void
    {
        $channel = Channel::factory()->create(['name' => 'Highway West']);

        $this->assertSame($channel->getKey(), $this->repository->findByNameInsensitive('  highway west ')?->getKey());
        $this->assertSame($channel->getKey(), $this->repository->findByNameInsensitive('HIGHWAY WEST')?->getKey());
    }

    public function testFindByNameInsensitiveReturnsNullWhenNoMatch(): void
    {
        Channel::factory()->create(['name' => 'Highway West']);

        $this->assertNull($this->repository->findByNameInsensitive('Nonexistent'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=ChannelRepositoryTest`
Expected: FAIL — `Call to undefined method ...::findByNameInsensitive()`.

- [ ] **Step 3: Add the method**

In `app/Repository/ChannelRepository.php`, after `findByName()`:

```php
    /**
     * Find a channel by name, ignoring case and surrounding whitespace.
     * @param string $name
     * @return Channel|null
     */
    public function findByNameInsensitive(string $name): ?Channel
    {
        return Channel::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->first();
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=ChannelRepositoryTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Repository/ChannelRepository.php tests/Integration/Repository/ChannelRepositoryTest.php
git commit -m "feat(#139): add case-insensitive channel name lookup to ChannelRepository"
```

---

### Task 3: `PreferredChannelService::resolveRawValue`

**Files:**
- Create: `app/Services/PreferredChannelService.php`
- Test: `tests/Integration/Services/PreferredChannelServiceTest.php`

**Interfaces:**
- Consumes: `ChannelRepository::findById(int $id): ?Channel`,
  `ChannelRepository::findByNameInsensitive(string $name): ?Channel`.
- Produces:
  - `PreferredChannelService::__construct(ChannelRepository $channelRepository)`
  - `PreferredChannelService::resolveRawValue(string $raw): ?int` — `''`/whitespace → `null`;
    `ctype_digit(trim($raw))` → ID lookup only (no name fallback); else → name lookup;
    resolved channel must have `is_video_reception_paused === false`, otherwise `null`.

- [ ] **Step 1: Write the failing test**

`tests/Integration/Services/PreferredChannelServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\Channel;
use App\Services\PreferredChannelService;
use Tests\DatabaseTestCase;

class PreferredChannelServiceTest extends DatabaseTestCase
{
    private PreferredChannelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PreferredChannelService::class);
    }

    public function testEmptyValueResolvesToNull(): void
    {
        $this->assertNull($this->service->resolveRawValue(''));
        $this->assertNull($this->service->resolveRawValue('   '));
    }

    public function testNumericValueResolvesById(): void
    {
        $channel = Channel::factory()->create();

        $this->assertSame($channel->getKey(), $this->service->resolveRawValue((string) $channel->getKey()));
    }

    public function testNumericValueForPausedChannelResolvesToNull(): void
    {
        $channel = Channel::factory()->paused()->create();

        $this->assertNull($this->service->resolveRawValue((string) $channel->getKey()));
    }

    public function testUnknownNumericIdResolvesToNull(): void
    {
        $this->assertNull($this->service->resolveRawValue('999999'));
    }

    public function testNameResolvesCaseInsensitively(): void
    {
        $channel = Channel::factory()->create(['name' => 'Highway West']);

        $this->assertSame($channel->getKey(), $this->service->resolveRawValue(' highway WEST '));
    }

    public function testNameForPausedChannelResolvesToNull(): void
    {
        Channel::factory()->paused()->create(['name' => 'Paused Lane']);

        $this->assertNull($this->service->resolveRawValue('Paused Lane'));
    }

    public function testUnknownNameResolvesToNull(): void
    {
        $this->assertNull($this->service->resolveRawValue('No Such Channel'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=PreferredChannelServiceTest`
Expected: FAIL — class `App\Services\PreferredChannelService` not found.

- [ ] **Step 3: Write the service**

`app/Services/PreferredChannelService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use App\Repository\ChannelRepository;

class PreferredChannelService
{
    public function __construct(private readonly ChannelRepository $channelRepository)
    {
    }

    /**
     * Resolve a raw preferred_channel value from info.csv to a channel id.
     *
     * Rules:
     *  - '' / whitespace only     → null (no preference)
     *  - digits only              → lookup by channel id (no name fallback)
     *  - otherwise                → lookup by name (case-insensitive, trimmed)
     * The channel must exist AND must not be paused
     * (is_video_reception_paused === false), otherwise null is returned.
     *
     * @param string $raw
     * @return int|null resolved channel id, or null when not resolvable
     */
    public function resolveRawValue(string $raw): ?int
    {
        $value = trim($raw);
        if ($value === '') {
            return null;
        }

        $channel = ctype_digit($value)
            ? $this->channelRepository->findById((int) $value)
            : $this->channelRepository->findByNameInsensitive($value);

        if (!$channel instanceof Channel || $channel->is_video_reception_paused) {
            return null;
        }

        return (int) $channel->getKey();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=PreferredChannelServiceTest`
Expected: PASS (7 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/PreferredChannelService.php tests/Integration/Services/PreferredChannelServiceTest.php
git commit -m "feat(#139): add PreferredChannelService raw-value resolution"
```

---

### Task 4: `InfoImporter` — 8th CSV column + resolution wiring

**Files:**
- Modify: `app/Services/InfoImporter.php` (constructor, `ROW_COLUMNS`, `sanitizeRow()`,
  `processRow()`, `createClip()`, `updateClipIfDirty()`)
- Modify: `tests/Integration/Services/InfoImporterTest.php` (`writeCsv()` → 8 columns; new tests)
- Modify: `tests/Fixtures/Videos/notizen.csv`, `tests/Fixtures/Inbox/Videos/notizen.csv`
- Test: `tests/Integration/Services/InfoImporterTest.php`

**Interfaces:**
- Consumes: `PreferredChannelService::resolveRawValue(string $raw): ?int`.
- Produces: after import, each `Clip` created/updated from a CSV row carries
  `preferred_channel` (raw string or `null`) and `preferred_channel_id` (`int|null`). An
  unresolvable non-empty value increments `ClipImportResult::stats->warnings` and calls
  `$onWarning`.

- [ ] **Step 1: Update `writeCsv()` in `InfoImporterTest.php` and add failing tests**

Change the header line and padding in `writeCsv()`:

```php
    private function writeCsv(array $rows): string
    {
        $path = sys_get_temp_dir().'/info_import_'.bin2hex(random_bytes(4)).'.csv';
        $fh = fopen($path, 'wb');
        fwrite($fh, "filename;start;end;note;bundle;role;submitted_by;preferred_channel\n");
        foreach ($rows as $row) {
            $line = implode(';', array_pad($row, 8, ''))."\n";
            fwrite($fh, $line);
        }
        fclose($fh);
        return $path;
    }
```

Add these test methods:

```php
    public function testImportResolvesPreferredChannelByName(): void
    {
        $video = Video::factory()->create(['original_name' => 'pref_by_name.mp4']);
        $channel = \App\Models\Channel::factory()->create(['name' => 'Highway West']);

        $csv = $this->writeCsv([
            ['pref_by_name.mp4', '00:00', '00:10', 'n', '', '', 'alice', 'highway west'],
        ]);

        $result = $this->infoImporter->import($csv);

        $this->assertSame(0, $result->stats->warnings);
        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'preferred_channel' => 'highway west',
            'preferred_channel_id' => $channel->getKey(),
        ]);

        @unlink($csv);
    }

    public function testImportResolvesPreferredChannelById(): void
    {
        $video = Video::factory()->create(['original_name' => 'pref_by_id.mp4']);
        $channel = \App\Models\Channel::factory()->create();

        $csv = $this->writeCsv([
            ['pref_by_id.mp4', '00:00', '00:10', 'n', '', '', 'alice', (string) $channel->getKey()],
        ]);

        $result = $this->infoImporter->import($csv);

        $this->assertSame(0, $result->stats->warnings);
        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'preferred_channel_id' => $channel->getKey(),
        ]);

        @unlink($csv);
    }

    public function testImportWarnsAndStoresRawValueForUnknownPreferredChannel(): void
    {
        $video = Video::factory()->create(['original_name' => 'pref_unknown.mp4']);

        $warnings = [];
        $csv = $this->writeCsv([
            ['pref_unknown.mp4', '00:00', '00:10', 'n', '', '', 'alice', 'No Such Channel'],
        ]);

        $result = $this->infoImporter->import($csv, [], function (string $m) use (&$warnings): void {
            $warnings[] = $m;
        });

        $this->assertSame(1, $result->stats->warnings);
        $this->assertNotEmpty($warnings);
        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'preferred_channel' => 'No Such Channel',
            'preferred_channel_id' => null,
        ]);

        @unlink($csv);
    }

    public function testImportWithoutPreferredChannelColumnKeepsExistingBehaviour(): void
    {
        $video = Video::factory()->create(['original_name' => 'legacy.mp4']);

        // 7-column row (no preferred_channel) — array_pad fills the 8th slot with ''.
        $csv = $this->writeCsv([
            ['legacy.mp4', '00:00', '00:10', 'n', '', '', 'alice'],
        ]);

        $result = $this->infoImporter->import($csv);

        $this->assertSame(0, $result->stats->warnings);
        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'preferred_channel' => null,
            'preferred_channel_id' => null,
        ]);

        @unlink($csv);
    }

    public function testReimportUpdatesPreferredChannelOnExistingClip(): void
    {
        $video = Video::factory()->create(['original_name' => 'reimport.mp4']);
        $channel = \App\Models\Channel::factory()->create(['name' => 'Second Lane']);

        $first = $this->writeCsv([['reimport.mp4', '00:00', '00:10', 'n', '', '', 'alice']]);
        $this->infoImporter->import($first);
        @unlink($first);

        $second = $this->writeCsv([['reimport.mp4', '00:00', '00:10', 'n', '', '', 'alice', 'Second Lane']]);
        $this->infoImporter->import($second);
        @unlink($second);

        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'preferred_channel_id' => $channel->getKey(),
        ]);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=InfoImporterTest`
Expected: FAIL — new columns not written; existing tests still pass.

- [ ] **Step 3: Update `InfoImporter` constructor and `ROW_COLUMNS`**

```php
    private const string CSV_DELIMITER = ';';
    private const int ROW_COLUMNS = 8;

    public function __construct(private readonly PreferredChannelService $preferredChannelService)
    {
    }
```

Add `use App\Services\PreferredChannelService;` at the top (same namespace `App\Services`, so a
`use` is not strictly required, but add it for clarity per house style — or reference directly).

- [ ] **Step 4: Update `sanitizeRow()`**

```php
    /**
     * Ensure row has the expected number of columns and trim values (incl. BOM).
     * @param  list<string|null>  $row
     * @return array{0:string,1:string,2:string,3:string,4:string,5:string,6:string,7:string}
     */
    private function sanitizeRow(array $row): array
    {
        $row = array_pad($row, self::ROW_COLUMNS, '');
        /** @var array{0:string,1:string,2:string,3:string,4:string,5:string,6:string,7:string} $mapped */
        $mapped = array_map(fn($v) => $this->trimUtf8Bom((string)$v), $row);

        [$filename, $start, $end, $note, $bundle, $role, $submittedBy, $preferredChannel] = $mapped;

        return [$filename, $start, $end, $note, $bundle, $role, $submittedBy, $preferredChannel];
    }
```

- [ ] **Step 5: Update `processRow()`**

Change the destructuring line and add resolution before the clip find/create:

```php
        [$filename, $start, $end, $note, $bundle, $role, $submittedBy, $preferredChannel] = $this->sanitizeRow($row);

        // Skip empty lines (no filename)
        if ($filename === '') {
            return;
        }

        $startSec = $this->parseTimeToSec($start, $onWarning, $result);
        $endSec = $this->parseTimeToSec($end, $onWarning, $result);

        $role = $this->inferRoleIfNeeded($filename, $role, $inferRole);
        [$bundle, $submittedBy] = $this->applyDefaults($bundle, $submittedBy, $defaultBundle, $defaultSubmitter);

        $baseName = basename($filename);

        $preferredChannelId = $this->resolvePreferredChannel($preferredChannel, $baseName, $onWarning, $result);

        $video = $this->findVideoOrWarn($baseName, $onWarning, $result);
        if (!$video) {
            return;
        }

        $clip = $this->findExistingClip(
            videoId: (int)$video->getKey(),
            startSec: $startSec,
            endSec: $endSec,
            role: $role
        );

        if ($clip) {
            $this->updateClipIfDirty($clip, $note, $bundle, $submittedBy, $preferredChannel, $preferredChannelId, $result);
        } else {
            $this->createClip(
                $video, $startSec, $endSec, $note, $bundle, $role, $submittedBy,
                $preferredChannel, $preferredChannelId, $result
            );
        }
```

Add the helper:

```php
    /**
     * Resolve the raw preferred_channel value; warn + count a warning when a
     * non-empty value cannot be resolved (unknown channel or paused channel).
     *
     * @param  string  $rawValue
     * @param  string  $baseName
     * @param  callable(string):void|null  $onWarning
     * @param  ClipImportResult  $result
     * @return int|null resolved channel id or null
     */
    private function resolvePreferredChannel(
        string $rawValue,
        string $baseName,
        ?callable $onWarning,
        ClipImportResult $result
    ): ?int {
        if ($rawValue === '') {
            return null;
        }

        $channelId = $this->preferredChannelService->resolveRawValue($rawValue);
        if ($channelId === null) {
            $result->incrementWarnings();
            if ($onWarning) {
                $onWarning("preferred_channel '{$rawValue}' nicht gefunden oder pausiert für filename='{$baseName}'");
            }
        }

        return $channelId;
    }
```

- [ ] **Step 6: Update `createClip()`**

```php
    private function createClip(
        Video $video,
        ?int $startSec,
        ?int $endSec,
        string $note,
        string $bundle,
        string $role,
        string $submittedBy,
        string $preferredChannel,
        ?int $preferredChannelId,
        ClipImportResult $result
    ): void {
        $clip = Clip::query()->create([
            'video_id' => $video->getKey(),
            'start_sec' => $startSec,
            'end_sec' => $endSec,
            'note' => $note !== '' ? $note : null,
            'bundle_key' => $bundle !== '' ? $bundle : null,
            'role' => $role !== '' ? $role : null,
            'submitted_by' => $submittedBy !== '' ? $submittedBy : null,
            'preferred_channel' => $preferredChannel !== '' ? $preferredChannel : null,
            'preferred_channel_id' => $preferredChannelId,
        ]);

        $result->addCreated($clip);
    }
```

- [ ] **Step 7: Update `updateClipIfDirty()`**

```php
    private function updateClipIfDirty(
        Clip $clip,
        string $note,
        string $bundle,
        string $submittedBy,
        string $preferredChannel,
        ?int $preferredChannelId,
        ClipImportResult $result
    ): void {
        $dirty = false;

        if ($note !== '' && $clip->note !== $note) {
            $clip->note = $note;
            $dirty = true;
        }
        if ($bundle !== '' && $clip->bundle_key !== $bundle) {
            $clip->bundle_key = $bundle;
            $dirty = true;
        }
        if ($submittedBy !== '' && $clip->submitted_by !== $submittedBy) {
            $clip->submitted_by = $submittedBy;
            $dirty = true;
        }
        if ($preferredChannel !== '' && $clip->preferred_channel !== $preferredChannel) {
            $clip->preferred_channel = $preferredChannel;
            $clip->preferred_channel_id = $preferredChannelId;
            $dirty = true;
        }

        if ($dirty) {
            $clip->save();
            $result->addUpdated($clip);
        }
    }
```

- [ ] **Step 8: Update fixtures**

`tests/Fixtures/Videos/notizen.csv` — replace both lines:

```
﻿filename(Genauer Dateiname im Ordner);start(MM:SS oder leer);end(MM:SS oder leer);note(Freitext/Anmerkung);bundle(Gemeinsame ID für zusammenhängende Videos);role(F=Front,R=Rear,etc.);submitted_by(Einsender/Quelle);preferred_channel(Kanalname oder -ID, optional)
windschatten_sprit_sparen.MP4;00:00;00:21;;; ;Ilya;
```

`tests/Fixtures/Inbox/Videos/notizen.csv`:

```
﻿filename(Genauer Dateiname im Ordner);start(MM:SS oder leer);end(MM:SS oder leer);note(Freitext/Anmerkung);bundle(Gemeinsame ID für zusammenhängende Videos);role(F=Front,R=Rear,etc.);submitted_by(Einsender/Quelle);preferred_channel(Kanalname oder -ID, optional)
example_F.mp4;00:00;00:21;Etwas super spannendes ;example; F;Max Mustermann;
example_R.mp4;00:00;00:21;etwas kurioses hinten;example;R;Max Mustermann;
standalone.mp4;00:05;00:15;hier passiert was;;F;Maxine Musterfrau;Highway West
```

- [ ] **Step 9: Run tests to verify they pass**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=InfoImporterTest`
Then the CSV/console suite:
`docker exec dashclip-delivery-sharing-1 php artisan test --filter=InfoImport`
`docker exec dashclip-delivery-sharing-1 php artisan test --filter=CsvService`
Expected: PASS (all old + 5 new importer tests). If any fixture-driven test asserts an exact
column count or the raw fixture string, update that assertion to include the trailing `;`.

- [ ] **Step 10: Commit**

```bash
git add app/Services/InfoImporter.php tests/Integration/Services/InfoImporterTest.php \
        tests/Fixtures/Videos/notizen.csv tests/Fixtures/Inbox/Videos/notizen.csv
git commit -m "feat(#139): import preferred_channel column from info.csv"
```

---

### Task 5: `CsvService::buildInfoCsv` — export column

**Files:**
- Modify: `app/Services/CsvService.php` (`buildInfoCsv()`)
- Test: `tests/Integration/Services/CsvServiceTest.php` (create if absent, else extend)

**Interfaces:**
- Consumes: `Clip::preferredChannel` relation, `Clip::$preferred_channel`.
- Produces: the exported `info.csv` header gains a trailing `preferred_channel` column; each clip
  row emits `clip->preferredChannel?->name ?? clip->preferred_channel` (empty when both null);
  the video-without-clips row emits an empty value.

- [ ] **Step 1: Write the failing test**

`tests/Integration/Services/CsvServiceTest.php` (add or create):

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\Video;
use App\Services\CsvService;
use Tests\DatabaseTestCase;

class CsvServiceTest extends DatabaseTestCase
{
    public function testBuildInfoCsvIncludesPreferredChannelColumn(): void
    {
        $channel = Channel::factory()->create(['name' => 'Highway West']);
        $video = Video::factory()->create(['original_name' => 'v.mp4']);
        Clip::factory()->for($video)->create([
            'preferred_channel' => 'highway west',
            'preferred_channel_id' => $channel->getKey(),
        ]);

        $assignment = Assignment::query()->create([
            'video_id' => $video->getKey(),
            'channel_id' => $channel->getKey(),
            'batch_id' => Batch::factory()->create()->getKey(),
            'status' => 'queued',
        ]);

        $csv = app(CsvService::class)->buildInfoCsv(
            Assignment::query()->whereKey($assignment->getKey())->with('video.clips')->get()
        );

        $lines = array_values(array_filter(explode("\n", trim($csv))));
        $this->assertStringContainsString('preferred_channel', $lines[0]);
        $this->assertStringContainsString('Highway West', $lines[1]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=CsvServiceTest`
Expected: FAIL — header lacks `preferred_channel`.

- [ ] **Step 3: Update `buildInfoCsv()`**

Header row:

```php
        $rows[] = ['filename', 'hash', 'size_mb', 'start', 'end', 'note', 'bundle', 'role', 'submitted_by', 'preferred_channel'];
```

Video-without-clips row — append one more `null` at the end of the pushed array.

Clip row — append as the last element:

```php
                    $clip->preferredChannel?->name ?? $clip->preferred_channel,
```

(The relation is eager-loadable; when not loaded Eloquent lazy-loads it. Callers that build large
CSVs should `->with('video.clips.preferredChannel')` — note this in the commit message but do not
change `ZipService` unless a test proves an N+1 regression.)

- [ ] **Step 4: Run test to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=CsvServiceTest`
Expected: PASS.

- [ ] **Step 5: Run the ZIP suite for regressions**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=Zip`
Expected: PASS. If a snapshot/exact-string assertion on the CSV header exists, update it.

- [ ] **Step 6: Commit**

```bash
git add app/Services/CsvService.php tests/Integration/Services/CsvServiceTest.php
git commit -m "feat(#139): export preferred_channel column in generated info.csv"
```

---

### Task 6: `PreferredChannelService` — `preloadForVideos` + `resolveForGroup`

**Files:**
- Modify: `app/Services/PreferredChannelService.php`
- Test: `tests/Integration/Services/PreferredChannelServiceTest.php`

**Interfaces:**
- Consumes: `Clip` model (`video_id`, `preferred_channel_id`).
- Produces:
  - `preloadForVideos(Collection $videos): array<int,int>` — `video_id => channel_id`, only for
    videos whose clips have exactly one distinct non-null `preferred_channel_id`; conflicting
    videos are omitted and logged via `Log::warning`.
  - `resolveForGroup(Collection $groupVideos, array $preferredChannelIdByVideo): ?int` — `null`
    when no video in the group has a preference; the shared id when all present preferences agree;
    `null` + `Log::warning` when they disagree.

- [ ] **Step 1: Write the failing tests**

Add to `PreferredChannelServiceTest.php`:

```php
    public function testPreloadForVideosReturnsUniquePreferencePerVideo(): void
    {
        $channel = \App\Models\Channel::factory()->create();
        $video = \App\Models\Video::factory()->create();
        \App\Models\Clip::factory()->count(2)->for($video)->create(['preferred_channel_id' => $channel->getKey()]);

        $map = $this->service->preloadForVideos(collect([$video]));

        $this->assertSame([$video->getKey() => $channel->getKey()], $map);
    }

    public function testPreloadForVideosOmitsVideosWithConflictingPreferences(): void
    {
        $a = \App\Models\Channel::factory()->create();
        $b = \App\Models\Channel::factory()->create();
        $video = \App\Models\Video::factory()->create();
        \App\Models\Clip::factory()->for($video)->create(['preferred_channel_id' => $a->getKey()]);
        \App\Models\Clip::factory()->for($video)->create(['preferred_channel_id' => $b->getKey()]);

        $map = $this->service->preloadForVideos(collect([$video]));

        $this->assertArrayNotHasKey($video->getKey(), $map);
    }

    public function testResolveForGroupReturnsSharedPreference(): void
    {
        $v1 = \App\Models\Video::factory()->create();
        $v2 = \App\Models\Video::factory()->create();
        $map = [$v1->getKey() => 7, $v2->getKey() => 7];

        $this->assertSame(7, $this->service->resolveForGroup(collect([$v1, $v2]), $map));
    }

    public function testResolveForGroupReturnsNullOnDisagreement(): void
    {
        $v1 = \App\Models\Video::factory()->create();
        $v2 = \App\Models\Video::factory()->create();
        $map = [$v1->getKey() => 7, $v2->getKey() => 9];

        $this->assertNull($this->service->resolveForGroup(collect([$v1, $v2]), $map));
    }

    public function testResolveForGroupReturnsNullWhenNoPreference(): void
    {
        $v1 = \App\Models\Video::factory()->create();

        $this->assertNull($this->service->resolveForGroup(collect([$v1]), []));
    }

    public function testResolveForGroupIgnoresVideosWithoutPreference(): void
    {
        $v1 = \App\Models\Video::factory()->create();
        $v2 = \App\Models\Video::factory()->create();
        $map = [$v1->getKey() => 7];

        $this->assertSame(7, $this->service->resolveForGroup(collect([$v1, $v2]), $map));
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=PreferredChannelServiceTest`
Expected: FAIL — undefined methods.

- [ ] **Step 3: Add the methods**

In `app/Services/PreferredChannelService.php` add `use` imports:

```php
use App\Models\Clip;
use App\Models\Video;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
```

Methods:

```php
    /**
     * Build a video_id => preferred channel_id map for a set of videos.
     *
     * Only videos whose clips carry exactly one distinct non-null
     * preferred_channel_id are included. Videos with conflicting values are
     * omitted and logged.
     *
     * @param  Collection<int,Video>  $videos
     * @return array<int,int>
     */
    public function preloadForVideos(Collection $videos): array
    {
        $videoIds = $videos->map(fn (Video $v) => (int) $v->getKey())->all();
        if ($videoIds === []) {
            return [];
        }

        $map = [];

        Clip::query()
            ->whereIn('video_id', $videoIds)
            ->whereNotNull('preferred_channel_id')
            ->get(['video_id', 'preferred_channel_id'])
            ->groupBy('video_id')
            ->each(function (Collection $clips, int|string $videoId) use (&$map): void {
                $distinct = $clips->pluck('preferred_channel_id')->map(fn ($id) => (int) $id)->unique()->values();

                if ($distinct->count() === 1) {
                    $map[(int) $videoId] = $distinct->first();
                    return;
                }

                Log::warning('conflicting preferred_channel for video {video}', [
                    'video' => (int) $videoId,
                    'channels' => $distinct->all(),
                ]);
            });

        return $map;
    }

    /**
     * Determine the effective preferred channel for a (bundle) group.
     *
     *  - no video in the group has a preference        → null
     *  - all present preferences are identical         → that channel_id
     *  - the group contains differing preferences      → null (logged)
     *
     * @param  Collection<int,Video>  $groupVideos
     * @param  array<int,int>  $preferredChannelIdByVideo
     * @return int|null
     */
    public function resolveForGroup(Collection $groupVideos, array $preferredChannelIdByVideo): ?int
    {
        $preferences = $groupVideos
            ->map(fn (Video $v) => $preferredChannelIdByVideo[(int) $v->getKey()] ?? null)
            ->filter(fn (?int $id) => $id !== null)
            ->unique()
            ->values();

        if ($preferences->isEmpty()) {
            return null;
        }

        if ($preferences->count() === 1) {
            return (int) $preferences->first();
        }

        Log::warning('conflicting preferred_channel within group {videos}', [
            'videos' => $groupVideos->map(fn (Video $v) => (int) $v->getKey())->all(),
            'channels' => $preferences->all(),
        ]);

        return null;
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=PreferredChannelServiceTest`
Expected: PASS (13 tests total).

- [ ] **Step 5: Commit**

```bash
git add app/Services/PreferredChannelService.php tests/Integration/Services/PreferredChannelServiceTest.php
git commit -m "feat(#139): collapse per-clip preferred_channel to per-video and per-group"
```

---

### Task 7: `ChannelService` — `channelAcceptsGroup` + `findPooledChannel`

**Files:**
- Modify: `app/Services/ChannelService.php` (extract acceptance check from `pickTargetChannel()`,
  add `findPooledChannel()`)
- Test: `tests/Integration/Services/ChannelServiceTest.php`

**Interfaces:**
- Consumes: nothing new.
- Produces:
  - `ChannelService::channelAcceptsGroup(Channel $channel, Collection $group, array $quota, array $blockedChannelIds, array $assignedChannelsByVideo): bool`
    — `true` when the channel has enough remaining quota for the whole group, is not in
    `$blockedChannelIds`, and no video of the group was ever assigned to it.
  - `ChannelService::findPooledChannel(Collection $rotationPool, int $channelId): ?Channel`
    — the channel instance from the rotation pool whose key equals `$channelId`, or `null`.
- `pickTargetChannel()` keeps its exact current behaviour (now delegates the per-candidate check
  to `channelAcceptsGroup()`).

- [ ] **Step 1: Write the failing tests**

Add to `tests/Integration/Services/ChannelServiceTest.php`:

```php
    public function testChannelAcceptsGroupChecksQuotaBlocksAndPriorAssignment(): void
    {
        $service = app(\App\Services\ChannelService::class);
        $channel = \App\Models\Channel::factory()->create();
        $v1 = \App\Models\Video::factory()->create();
        $group = collect([$v1]);

        $quota = [$channel->getKey() => 1];

        $this->assertTrue($service->channelAcceptsGroup($channel, $group, $quota, [], []));
        $this->assertFalse($service->channelAcceptsGroup($channel, $group, [$channel->getKey() => 0], [], []));
        $this->assertFalse($service->channelAcceptsGroup($channel, $group, $quota, [$channel->getKey()], []));
        $this->assertFalse($service->channelAcceptsGroup(
            $channel,
            $group,
            $quota,
            [],
            [$v1->getKey() => collect([$channel->getKey()])]
        ));
    }

    public function testFindPooledChannelReturnsMatchOrNull(): void
    {
        $service = app(\App\Services\ChannelService::class);
        $a = \App\Models\Channel::factory()->create();
        $b = \App\Models\Channel::factory()->create();
        $pool = collect([$a, $a, $b]);

        $this->assertSame($a->getKey(), $service->findPooledChannel($pool, $a->getKey())?->getKey());
        $this->assertNull($service->findPooledChannel($pool, 999999));
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=ChannelServiceTest`
Expected: FAIL — undefined methods.

- [ ] **Step 3: Extract `channelAcceptsGroup()` and add `findPooledChannel()`**

In `app/Services/ChannelService.php`, add:

```php
    /**
     * Whether a specific channel can take the whole group: enough remaining
     * quota, not blocked, and never previously assigned to any video in the
     * group.
     *
     * @param  Collection<int,Video>  $group
     * @param  array<int,int>  $quota
     * @param  array<int,int>  $blockedChannelIds
     * @param  array<int,Collection<int,int>>  $assignedChannelsByVideo
     */
    public function channelAcceptsGroup(
        Channel $channel,
        Collection $group,
        array $quota,
        array $blockedChannelIds,
        array $assignedChannelsByVideo
    ): bool {
        if (($quota[$channel->getKey()] ?? 0) < $group->count()) {
            return false;
        }

        if (in_array($channel->getKey(), $blockedChannelIds, true)) {
            return false;
        }

        $alreadyAssigned = $group->some(function (Video $v) use ($channel, $assignedChannelsByVideo) {
            $assigned = $assignedChannelsByVideo[$v->getKey()] ?? collect();
            return $assigned->contains($channel->getKey());
        });

        return !$alreadyAssigned;
    }

    /**
     * Return the channel instance from the rotation pool matching $channelId,
     * or null when that channel is not part of the (uploader-/team-specific)
     * pool (paused, out of team scope, deleted).
     *
     * @param  Collection<int,Channel>  $rotationPool
     */
    public function findPooledChannel(Collection $rotationPool, int $channelId): ?Channel
    {
        return $rotationPool->first(fn (Channel $channel) => (int) $channel->getKey() === $channelId);
    }
```

Refactor the `while` body of `pickTargetChannel()` to use the new method:

```php
        while ($rotations < $poolCount) {
            /** @var Channel $candidate */
            $candidate = $rotationPool->first();
            $rotationPool->push($rotationPool->shift());
            $rotations++;

            if ($this->channelAcceptsGroup($candidate, $group, $quota, $blockedChannelIds, $assignedChannelsByVideo)) {
                return $candidate;
            }
        }

        return null;
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=ChannelServiceTest`
Then the distributor suite (behaviour must be unchanged):
`docker exec dashclip-delivery-sharing-1 php artisan test --filter=AssignmentDistributor`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/ChannelService.php tests/Integration/Services/ChannelServiceTest.php
git commit -m "refactor(#139): extract channel acceptance check and add pooled-channel lookup"
```

---

### Task 8: Thread `viaPreferred` + `deferred` through assignment write path

**Files:**
- Modify: `app/ValueObjects/AssignmentRun.php` (new readonly `preferredChannelIdByVideo` field)
- Modify: `app/Services/AssignmentService.php` (`assignGroupToChannel()` gains `bool $viaPreferred`)
- Modify: `app/Repository/AssignmentRepository.php` (`createAssignment()` gains `bool $viaPreferred`)
- Modify: `app/Services/BatchService.php` (`finishAssignBatch()` gains `int $deferred = 0`)
- Modify: `app/Repository/BatchRepository.php` (`markAssignedBatchAsFinished()` gains `int $deferred = 0`)
- Test: `tests/Integration/Services/AssignmentServiceTest.php`,
  `tests/Integration/Repository/` (existing repo tests if present)

**Interfaces:**
- Consumes: nothing new.
- Produces:
  - `AssignmentRepository::createAssignment(Video $video, Channel $channel, Batch $batch, bool $viaPreferred = false): Assignment`
    — writes `via_preferred_channel => $viaPreferred`.
  - `AssignmentService::assignGroupToChannel(Collection $group, Channel $channel, AssignmentRun $run, bool $viaPreferred = false): int`
  - `AssignmentRun` constructor gains `array $preferredChannelIdByVideo` (readonly), placed after
    `assignedChannelsByVideo`.
  - `BatchService::finishAssignBatch(Batch $batch, int $assigned, int $skipped, int $deferred = 0): bool`
  - `BatchRepository::markAssignedBatchAsFinished(Batch $batch, int $assigned, int $skipped, int $deferred = 0): bool`
    — `stats` becomes `['assigned' => …, 'skipped' => …, 'deferred' => …]`.

- [ ] **Step 1: Write the failing test**

Add to `tests/Integration/Services/AssignmentServiceTest.php`:

```php
    public function testAssignGroupToChannelFlagsViaPreferredChannel(): void
    {
        $service = app(\App\Services\AssignmentService::class);
        $channel = \App\Models\Channel::factory()->create();
        $video = \App\Models\Video::factory()->create();
        $batch = \App\Models\Batch::factory()->create();

        $run = new \App\ValueObjects\AssignmentRun(
            groups: collect([collect([$video])]),
            channelPool: new \App\DTO\ChannelPoolDto(
                channels: collect([$channel]),
                rotationPool: collect([$channel]),
                quota: [$channel->getKey() => 5],
            ),
            blockedByVideo: [],
            assignedChannelsByVideo: [],
            preferredChannelIdByVideo: [$video->getKey() => $channel->getKey()],
            batch: $batch,
            uploaderType: 'user',
            uploaderId: 0,
        );

        $count = $service->assignGroupToChannel(collect([$video]), $channel, $run, viaPreferred: true);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('assignments', [
            'video_id' => $video->getKey(),
            'channel_id' => $channel->getKey(),
            'via_preferred_channel' => true,
        ]);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=AssignmentServiceTest`
Expected: FAIL — `AssignmentRun` has no `preferredChannelIdByVideo` argument.

- [ ] **Step 3: Update `AssignmentRun`**

```php
    public function __construct(
        public readonly Collection $groups,
        public readonly ChannelPoolDto $channelPool,
        public readonly array $blockedByVideo,
        public array $assignedChannelsByVideo,
        public readonly array $preferredChannelIdByVideo,
        public readonly Batch $batch,
        public readonly string $uploaderType,
        public readonly string|int $uploaderId,
    ) {
    }
```

- [ ] **Step 4: Update `AssignmentRepository::createAssignment()`**

```php
    public function createAssignment(Video $video, Channel $channel, Batch $batch, bool $viaPreferred = false): Assignment
    {
        return Assignment::query()->create([
            'video_id' => $video->getKey(),
            'channel_id' => $channel->getKey(),
            'batch_id' => $batch->getKey(),
            'status' => StatusEnum::QUEUED->value,
            'via_preferred_channel' => $viaPreferred,
        ]);
    }
```

- [ ] **Step 5: Update `AssignmentService::assignGroupToChannel()`**

```php
    public function assignGroupToChannel(
        Collection $group,
        Channel $channel,
        AssignmentRun $run,
        bool $viaPreferred = false
    ): int {
        $count = 0;
        foreach ($group as $video) {
            $this->assignmentRepository->createAssignment($video, $channel, $run->batch, $viaPreferred);

            $run->recordAssignment($video->getKey(), $channel->getKey());
            $run->decrementQuota($channel->getKey());
            $count++;
        }

        return $count;
    }
```

- [ ] **Step 6: Update `BatchRepository::markAssignedBatchAsFinished()` and `BatchService::finishAssignBatch()`**

`BatchRepository`:

```php
    public function markAssignedBatchAsFinished(
        Batch $batch,
        int $assigned,
        int $skipped,
        int $deferred = 0
    ): bool {
        return $batch->update([
            'finished_at' => now(),
            'stats' => [
                'assigned' => $assigned,
                'skipped' => $skipped,
                'deferred' => $deferred,
            ],
        ]);
    }
```

`BatchService`:

```php
    public function finishAssignBatch(Batch $batch, int $assigned, int $skipped, int $deferred = 0): bool
    {
        return $this->batchRepository->markAssignedBatchAsFinished($batch, $assigned, $skipped, $deferred);
    }
```

- [ ] **Step 7: Fix the instrumented stub and its mock expectations**

`tests/Integration/Services/Stubs/InstrumentedAssignmentDistributor.php` — `assignGroups()` returns
`[0, 0, 0]`:

```php
    public function assignGroups(AssignmentRun $run): array
    {
        $this->assignGroupRuns->push($run);

        return [0, 0, 0];
    }
```

`tests/Integration/Services/AssignmentDistributorTest.php` — update the three
`->shouldHaveReceived('finishAssignBatch')->with($stubs->batch, 0, 0)` calls to
`->with($stubs->batch, 0, 0, 0)`, and change
`$this->assertSame(['assigned' => 1, 'skipped' => 0], $result);` in
`testDistributorHandlesInitialRunWithoutPreviousBatch` to
`$this->assertSame(['assigned' => 1, 'skipped' => 0, 'deferred' => 0], $result);`.

- [ ] **Step 8: Run tests to verify they pass**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=AssignmentServiceTest`
`docker exec dashclip-delivery-sharing-1 php artisan test --filter=AssignmentDistributor`
`docker exec dashclip-delivery-sharing-1 php artisan test --filter=Batch`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add app/ValueObjects/AssignmentRun.php app/Services/AssignmentService.php \
        app/Repository/AssignmentRepository.php app/Services/BatchService.php \
        app/Repository/BatchRepository.php \
        tests/Integration/Services/Stubs/InstrumentedAssignmentDistributor.php \
        tests/Integration/Services/AssignmentDistributorTest.php \
        tests/Integration/Services/AssignmentServiceTest.php
git commit -m "feat(#139): thread via_preferred_channel flag and deferred counter through assignment writes"
```

---

### Task 9: `AssignmentDistributor` — preferred-channel branch

**Files:**
- Modify: `app/Services/AssignmentDistributor.php` (`distribute()` preload + `AssignmentRun`
  construction; `assignGroups()` new branch; return shape; `finishAssignBatch` call)
- Modify: `app/Console/Commands/AssignDistribute.php` (report `deferred`)
- Test: `tests/Integration/Services/PreferredChannelDistributionTest.php` (new)

**Interfaces:**
- Consumes:
  - `PreferredChannelService::preloadForVideos(Collection $videos): array<int,int>`
  - `PreferredChannelService::resolveForGroup(Collection $groupVideos, array $map): ?int`
  - `ChannelService::findPooledChannel(Collection $rotationPool, int $channelId): ?Channel`
  - `ChannelService::channelAcceptsGroup(Channel, Collection, array, array, array): bool`
  - `AssignmentService::assignGroupToChannel(Collection, Channel, AssignmentRun, bool): int`
  - `AssignmentRun::$preferredChannelIdByVideo`
- Produces:
  - `AssignmentDistributor::assignGroups(AssignmentRun $run): array` returns
    `[int $assigned, int $skipped, int $deferred]`.
  - `AssignmentDistributor::distribute(?int $quotaOverride = null): array` returns
    `['assigned' => int, 'skipped' => int, 'deferred' => int]`.

- [ ] **Step 1: Write the failing tests**

`tests/Integration/Services/PreferredChannelDistributionTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Enum\ProcessingStatusEnum;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\Video;
use App\Services\AssignmentDistributor;
use Tests\DatabaseTestCase;

class PreferredChannelDistributionTest extends DatabaseTestCase
{
    private AssignmentDistributor $distributor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->distributor = app(AssignmentDistributor::class);
        // Start from a clean, deterministic channel set.
        Channel::query()->delete();
    }

    private function video(string $hash): Video
    {
        return Video::create([
            'hash' => $hash,
            'path' => $hash,
            'processing_status' => ProcessingStatusEnum::Completed,
        ]);
    }

    public function testValidPreferredChannelWinsOverAlgorithm(): void
    {
        $wanted = Channel::factory()->create(['name' => 'Wanted', 'weight' => 1, 'weekly_quota' => 10]);
        $other = Channel::factory()->create(['name' => 'Other', 'weight' => 50, 'weekly_quota' => 10]);

        $video = $this->video('h1');
        Clip::create(['video_id' => $video->id, 'preferred_channel_id' => $wanted->getKey()]);

        $result = $this->distributor->distribute();

        $this->assertSame(1, $result['assigned']);
        $this->assertDatabaseHas('assignments', [
            'video_id' => $video->getKey(),
            'channel_id' => $wanted->getKey(),
            'via_preferred_channel' => true,
        ]);
        $this->assertDatabaseMissing('assignments', [
            'video_id' => $video->getKey(),
            'channel_id' => $other->getKey(),
        ]);
    }

    public function testNoPreferenceUsesAlgorithmAndFlagIsFalse(): void
    {
        Channel::factory()->create(['weekly_quota' => 10]);
        $video = $this->video('h2');
        Clip::create(['video_id' => $video->id]);

        $result = $this->distributor->distribute();

        $this->assertSame(1, $result['assigned']);
        $this->assertDatabaseHas('assignments', [
            'video_id' => $video->getKey(),
            'via_preferred_channel' => false,
        ]);
    }

    public function testPausedPreferredChannelFallsBackToAlgorithm(): void
    {
        $paused = Channel::factory()->paused()->create(['name' => 'Paused', 'weekly_quota' => 10]);
        $active = Channel::factory()->create(['name' => 'Active', 'weekly_quota' => 10]);

        $video = $this->video('h3');
        // preferred_channel_id can still point at the paused channel from an earlier import;
        // the distributor must not use it because it is not in the pool.
        Clip::create(['video_id' => $video->id, 'preferred_channel_id' => $paused->getKey()]);

        $result = $this->distributor->distribute();

        $this->assertSame(1, $result['assigned']);
        $this->assertDatabaseHas('assignments', [
            'video_id' => $video->getKey(),
            'channel_id' => $active->getKey(),
            'via_preferred_channel' => false,
        ]);
    }

    public function testExhaustedQuotaOnPreferredChannelDefersInsteadOfReassigning(): void
    {
        $wanted = Channel::factory()->create(['name' => 'Wanted', 'weight' => 1, 'weekly_quota' => 0]);
        $other = Channel::factory()->create(['name' => 'Other', 'weight' => 1, 'weekly_quota' => 10]);

        $video = $this->video('h4');
        Clip::create(['video_id' => $video->id, 'preferred_channel_id' => $wanted->getKey()]);

        $result = $this->distributor->distribute();

        $this->assertSame(0, $result['assigned']);
        $this->assertSame(1, $result['deferred']);
        $this->assertDatabaseMissing('assignments', ['video_id' => $video->getKey()]);

        // Next run with quota freed up → lands on the wished channel.
        $wanted->update(['weekly_quota' => 10]);
        $second = $this->distributor->distribute();

        $this->assertSame(1, $second['assigned']);
        $this->assertDatabaseHas('assignments', [
            'video_id' => $video->getKey(),
            'channel_id' => $wanted->getKey(),
            'via_preferred_channel' => true,
        ]);
    }

    public function testBundleGroupWithMatchingPreferenceGoesToWishedChannel(): void
    {
        $wanted = Channel::factory()->create(['name' => 'Wanted', 'weekly_quota' => 10]);
        Channel::factory()->create(['name' => 'Other', 'weight' => 99, 'weekly_quota' => 10]);

        $v1 = $this->video('h5a');
        $v2 = $this->video('h5b');
        Clip::create(['video_id' => $v1->id, 'bundle_key' => 'B', 'preferred_channel_id' => $wanted->getKey()]);
        Clip::create(['video_id' => $v2->id, 'bundle_key' => 'B', 'preferred_channel_id' => $wanted->getKey()]);

        $result = $this->distributor->distribute();

        $this->assertSame(2, $result['assigned']);
        $this->assertSame(
            [$wanted->getKey()],
            Assignment::query()->whereIn('video_id', [$v1->id, $v2->id])->pluck('channel_id')->unique()->values()->all()
        );
    }

    public function testBundleGroupWithConflictingPreferenceUsesAlgorithm(): void
    {
        $a = Channel::factory()->create(['name' => 'A', 'weekly_quota' => 10]);
        $b = Channel::factory()->create(['name' => 'B', 'weekly_quota' => 10]);

        $v1 = $this->video('h6a');
        $v2 = $this->video('h6b');
        Clip::create(['video_id' => $v1->id, 'bundle_key' => 'B', 'preferred_channel_id' => $a->getKey()]);
        Clip::create(['video_id' => $v2->id, 'bundle_key' => 'B', 'preferred_channel_id' => $b->getKey()]);

        $result = $this->distributor->distribute();

        $this->assertSame(2, $result['assigned']);
        foreach (Assignment::query()->whereIn('video_id', [$v1->id, $v2->id])->get() as $assignment) {
            $this->assertFalse($assignment->via_preferred_channel);
        }
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=PreferredChannelDistributionTest`
Expected: FAIL — `$result['deferred']` undefined / preferred channel ignored.

- [ ] **Step 3: Add the preload and pass it into `AssignmentRun`**

In `AssignmentDistributor::distribute()`, step 5 (preloads), add:

```php
                $preferredChannelIdByVideo = app(PreferredChannelService::class)
                    ->preloadForVideos($videosOfUploader);
```

Step 6, add the argument to the `new AssignmentRun(...)` call:

```php
                    assignedChannelsByVideo: $assignedChannelsByVideo,
                    preferredChannelIdByVideo: $preferredChannelIdByVideo,
                    batch: $batch,
```

Add `use App\Services\PreferredChannelService;` at the top.

Track deferred totals:

```php
        $totalAssigned = 0;
        $totalSkipped = 0;
        $totalDeferred = 0;
```

```php
                [$assigned, $skipped, $deferred] = $this->assignGroups($run);
                $totalAssigned += $assigned;
                $totalSkipped += $skipped;
                $totalDeferred += $deferred;
```

```php
        $batchService->finishAssignBatch($batch, $totalAssigned, $totalSkipped, $totalDeferred);

        return ['assigned' => $totalAssigned, 'skipped' => $totalSkipped, 'deferred' => $totalDeferred];
```

Also update the two early-abort calls in `collectPoolOrAbort()` and `prepareChannelsOrAbort()` —
they call `finishAssignBatch($batch, 0, 0)`; leave them (the new 4th param defaults to `0`).

- [ ] **Step 4: Rewrite `assignGroups()`**

```php
    public function assignGroups(AssignmentRun $run): array
    {
        $assigned = 0;
        $skipped = 0;
        $deferred = 0;

        $channelService = app(ChannelService::class);
        $assignmentService = app(AssignmentService::class);
        $preferredChannelService = app(PreferredChannelService::class);

        foreach ($run->groups as $group) {
            $blockedChannelIds = $this->calculateBlockedChannels($group, $run->blockedByVideo);

            $preferredChannelId = $preferredChannelService->resolveForGroup($group, $run->preferredChannelIdByVideo);

            if ($preferredChannelId !== null) {
                $preferredOutcome = $this->assignToPreferredChannel(
                    $group,
                    $preferredChannelId,
                    $blockedChannelIds,
                    $run,
                    $channelService,
                    $assignmentService
                );

                if ($preferredOutcome === 'assigned') {
                    $assigned += $group->count();
                    if ($run->quotasUsedUp()) {
                        break;
                    }
                    continue;
                }

                if ($preferredOutcome === 'deferred') {
                    $deferred += $group->count();
                    continue;
                }
                // 'fallthrough' → normal round-robin below
            }

            $channel = $channelService->pickTargetChannel(
                $group,
                $run->channelPool->rotationPool,
                $run->channelPool->quota,
                $blockedChannelIds,
                $run->assignedChannelsByVideo
            );

            if (!$channel) {
                $skipped += $group->count();
                continue;
            }

            $assigned += $assignmentService->assignGroupToChannel($group, $channel, $run);

            if ($run->quotasUsedUp()) {
                break;
            }
        }

        return [$assigned, $skipped, $deferred];
    }

    /**
     * Try to place a group on its wished channel.
     *
     * @return 'assigned'|'deferred'|'fallthrough'
     *   - 'assigned'    the group was assigned to the wished channel
     *   - 'deferred'    the wished channel is valid but cannot take the group
     *                   right now (quota/block) — retry next run
     *   - 'fallthrough' the wished channel is not usable at all (not in pool)
     *                   or the wish is already fulfilled — use the algorithm
     */
    private function assignToPreferredChannel(
        Collection $group,
        int $preferredChannelId,
        array $blockedChannelIds,
        AssignmentRun $run,
        ChannelService $channelService,
        AssignmentService $assignmentService
    ): string {
        $channel = $channelService->findPooledChannel($run->channelPool->rotationPool, $preferredChannelId);

        if ($channel === null) {
            return 'fallthrough';
        }

        // Wish already fulfilled for every video in the group → let the
        // algorithm handle any remaining distribution instead of deferring
        // forever.
        $allAlreadyOnPreferred = $group->every(function ($video) use ($preferredChannelId, $run) {
            $assigned = $run->assignedChannelsByVideo[$video->getKey()] ?? collect();
            return $assigned->contains($preferredChannelId);
        });
        if ($allAlreadyOnPreferred) {
            return 'fallthrough';
        }

        $accepts = $channelService->channelAcceptsGroup(
            $channel,
            $group,
            $run->channelPool->quota,
            $blockedChannelIds,
            $run->assignedChannelsByVideo
        );

        if (!$accepts) {
            return 'deferred';
        }

        $assignmentService->assignGroupToChannel($group, $channel, $run, viaPreferred: true);

        return 'assigned';
    }
```

- [ ] **Step 5: Update `AssignDistribute` command output**

```php
            $stats = $this->distributor->distribute($quota !== null ? (int)$quota : null);
            $this->info("Assigned={$stats['assigned']}, skipped={$stats['skipped']}, deferred={$stats['deferred']}");
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=PreferredChannelDistributionTest`
`docker exec dashclip-delivery-sharing-1 php artisan test --filter=AssignmentDistributor`
`docker exec dashclip-delivery-sharing-1 php artisan test --filter=AssignDistribute`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Services/AssignmentDistributor.php app/Console/Commands/AssignDistribute.php \
        tests/Integration/Services/PreferredChannelDistributionTest.php
git commit -m "feat(#139): honour preferred_channel in the distribution algorithm"
```

---

### Task 10: Admin overview — column, filter, translations

**Files:**
- Modify: `app/Filament/Admin/Resources/Assignments/AssignmentResource.php` (column + filter)
- Modify: `lang/de/filament.php`, `lang/en/filament.php` (`admin.labels.via_preferred_channel`)
- Test: `tests/Feature/Filament/Admin/AssignmentPreferredChannelColumnTest.php` (new)

**Interfaces:**
- Consumes: `assignments.via_preferred_channel` (bool), `Assignment` model cast.
- Produces: an `IconColumn` `via_preferred_channel` and a `TernaryFilter` of the same name in the
  admin assignments table.

- [ ] **Step 1: Write the failing test**

Check an existing admin Filament test for the login/panel helper pattern first
(`ls tests/Feature/Filament` — reuse whatever `actingAs(adminUser)` helper the suite already has;
the seeder creates an admin via `AdminSeeder`). Then:

`tests/Feature/Filament/Admin/AssignmentPreferredChannelColumnTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Admin;

use App\Filament\Admin\Resources\Assignments\Pages\ListAssignments;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\User;
use App\Models\Video;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

class AssignmentPreferredChannelColumnTest extends DatabaseTestCase
{
    public function testListShowsPreferredChannelColumnAndFilters(): void
    {
        $this->actingAs(User::whereNotNull('email')->first() ?? User::factory()->create());

        $channel = Channel::factory()->create();
        $batch = Batch::factory()->create();

        $viaPreferred = Assignment::query()->create([
            'video_id' => Video::factory()->create()->getKey(),
            'channel_id' => $channel->getKey(),
            'batch_id' => $batch->getKey(),
            'status' => 'queued',
            'via_preferred_channel' => true,
        ]);
        $viaAlgorithm = Assignment::query()->create([
            'video_id' => Video::factory()->create()->getKey(),
            'channel_id' => $channel->getKey(),
            'batch_id' => $batch->getKey(),
            'status' => 'queued',
            'via_preferred_channel' => false,
        ]);

        Livewire::test(ListAssignments::class)
            ->assertCanSeeTableRecords([$viaPreferred, $viaAlgorithm])
            ->assertTableColumnExists('via_preferred_channel')
            ->filterTable('via_preferred_channel', true)
            ->assertCanSeeTableRecords([$viaPreferred])
            ->assertCanNotSeeTableRecords([$viaAlgorithm]);
    }
}
```

> If the suite has no existing admin-panel Livewire test to copy an auth helper from, fall back to
> an integration assertion: build the two assignments and assert
> `Assignment::query()->where('via_preferred_channel', true)->pluck('id')` returns only the
> wished one — the column/filter wiring is then covered by manual check + the code review.

- [ ] **Step 2: Run test to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=AssignmentPreferredChannelColumnTest`
Expected: FAIL — column `via_preferred_channel` does not exist on the table.

- [ ] **Step 3: Add the column**

In `AssignmentResource::table()`, add to the `->columns([...])` array (after the `status` column),
and add `use Filament\Tables\Columns\IconColumn;`:

```php
                IconColumn::make('via_preferred_channel')
                    ->label(__('filament.admin.labels.via_preferred_channel'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
```

- [ ] **Step 4: Add the filter**

Add `use Filament\Tables\Filters\TernaryFilter;` and, in `->filters([...])`:

```php
                TernaryFilter::make('via_preferred_channel')
                    ->label(__('filament.admin.labels.via_preferred_channel')),
```

- [ ] **Step 5: Add translations**

`lang/de/filament.php` — in the `admin` → `labels` array (alongside `'attempts' => 'Versuche',`):

```php
            'via_preferred_channel' => 'Wunschkanal',
```

`lang/en/filament.php` — same key in the matching array:

```php
            'via_preferred_channel' => 'Preferred channel',
```

- [ ] **Step 6: Run test to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=AssignmentPreferredChannelColumnTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Admin/Resources/Assignments/AssignmentResource.php \
        lang/de/filament.php lang/en/filament.php \
        tests/Feature/Filament/Admin/AssignmentPreferredChannelColumnTest.php
git commit -m "feat(#139): surface via_preferred_channel in the admin assignment overview"
```

---

### Task 11: Changelog + full suite

**Files:**
- Modify: `CHANGELOG.md`

- [ ] **Step 1: Add the changelog entry**

Under `## [Unreleased]`, in the appropriate `### Added` block (create it if missing), English,
lines ≤ 120 chars:

```markdown
### Added
- `preferred_channel` column in `info.csv`: submitters can target a specific channel by name or id.
  The importer validates it (unknown or paused channels raise a warning and fall back to the normal
  algorithm) and the distributor honours it without overriding quotas, blocks or team scope; a
  wished channel that is temporarily out of quota defers the video to the next run (#139).
- Admin assignment overview shows and filters whether an assignment was made via `preferred_channel`.
```

- [ ] **Step 2: Run the full test suite**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --parallel`
Expected: PASS (no regressions). Investigate and fix any failure before committing.

- [ ] **Step 3: Run Pint**

Run: `docker exec dashclip-delivery-sharing-1 ./vendor/bin/pint --dirty`
Expected: clean (or auto-fixes applied — re-stage and include).

- [ ] **Step 4: Commit**

```bash
git add CHANGELOG.md
git commit -m "docs(#139): changelog entry for preferred_channel"
```

---

## Self-Review

**1. Spec coverage:**

| Spec section | Task(s) |
|---|---|
| Datenmodell — `clips` columns, `assignments.via_preferred_channel` | Task 1 |
| Import — 8th CSV column, positional, backward compatible | Task 4 |
| Import — `PreferredChannelService::resolveRawValue` (id/name/paused rules) | Task 3 |
| Import — `ChannelRepository::findByNameInsensitive` | Task 2 |
| Import — warning on unresolvable value, raw value always stored | Task 4 |
| Import — `createClip`/`updateClipIfDirty` new fields | Task 4 |
| Export — `CsvService::buildInfoCsv` new column | Task 5 |
| Distribution — `preloadForVideos` + `resolveForGroup` (conflict handling) | Task 6 |
| Distribution — `ChannelService::channelAcceptsGroup` extraction + `findPooledChannel` | Task 7 |
| Distribution — `AssignmentRun.preferredChannelIdByVideo`, `viaPreferred` plumbing | Task 8 |
| Distribution — `deferred` counter, `batches.stats`, `finishAssignBatch` signature | Task 8, Task 9 |
| Distribution — `assignGroups` branch (assigned/deferred/fallthrough), team scope + paused via "not in pool" | Task 9 |
| Distribution — "wish already fulfilled" avoids infinite defer | Task 9 |
| Admin — `IconColumn` + `TernaryFilter` + de/en translations | Task 10 |
| Tests — valid → wished channel; invalid/missing → normal; quota → defer; bundle agree/conflict; paused; team scope | Task 3, 6, 9 |
| Changelog (ADR 0006) | Task 11 |
| Fixtures updated | Task 4 |
| `AssignDistribute` command output | Task 9 |

Team-scope-mismatch is covered implicitly: a channel outside the team's `channel_team` pool is not
in `rotationPool`, so `findPooledChannel()` returns `null` → `fallthrough`. No dedicated task/test
is strictly required, but add one to `PreferredChannelDistributionTest` if a `Team` + `channel_team`
factory path is readily available (check `FakeDistributorDependencies::createTeam()` and whether
channels can be attached to a team via factory); if the setup is heavy, the paused-channel test
already exercises the identical "not in pool" code path.

**2. Placeholder scan:** No "TBD"/"TODO"/"handle edge cases" — every code step has concrete code.
The only conditional guidance is the Task 10 fallback (explicit, with a concrete alternative
assertion) and the Task 9 team-scope note (explicit reasoning + concrete fallback).

**3. Type consistency:**
- `resolveRawValue(string): ?int` — defined Task 3, consumed Task 4. ✓
- `findByNameInsensitive(string): ?Channel` — defined Task 2, consumed Task 3. ✓
- `preloadForVideos(Collection): array` / `resolveForGroup(Collection, array): ?int` — defined
  Task 6, consumed Task 9. ✓
- `channelAcceptsGroup(Channel, Collection, array, array, array): bool` /
  `findPooledChannel(Collection, int): ?Channel` — defined Task 7, consumed Task 9. ✓
- `assignGroupToChannel(Collection, Channel, AssignmentRun, bool)` — signature changed Task 8,
  called Task 9 with `viaPreferred:` named arg. ✓
- `createAssignment(Video, Channel, Batch, bool)` — Task 8; no other callers (verified: only
  `AssignmentService::assignGroupToChannel`). ✓
- `AssignmentRun` constructor: `preferredChannelIdByVideo` inserted after `assignedChannelsByVideo`
  — Task 8 updates the VO, the test in Task 8 and the real construction in Task 9 both use named
  args, and `InstrumentedAssignmentDistributor` does not construct `AssignmentRun` directly. ✓
- `finishAssignBatch(Batch, int, int, int)` / `markAssignedBatchAsFinished(Batch, int, int, int)`
  — Task 8; early-abort callers rely on the `= 0` default; the 3 mock expectations updated in
  Task 8 Step 7. ✓
- `distribute()` return `['assigned','skipped','deferred']` — Task 9; `AssignDistribute` updated
  same task; `AssignmentDistributorTest` exact-match assertion updated Task 8 Step 7. ✓

## Execution Handoff

**Plan complete and saved to `docs/superpowers/plans/2026-09-05-preferred-channel.md`. Two execution options:**

**1. Subagent-Driven (recommended)** - I dispatch a fresh subagent per task, review between tasks, fast iteration

**2. Inline Execution** - Execute tasks in this session using executing-plans, batch execution with checkpoints

**Which approach?**
