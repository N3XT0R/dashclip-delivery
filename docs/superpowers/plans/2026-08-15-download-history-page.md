# Download-Verlauf-Seite (Standard-Panel) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a new, read-only Filament page in the Standard panel's "Meine Inhalte" navigation group that lists, per download event, which of the authenticated user's videos was downloaded by which channel, sorted reverse-chronologically.

**Architecture:** A new `DownloadRepository::forUser()` method encapsulates the data-access query (reusing the existing `Assignment::scopeHasUsersClips()` scope). A new `App\Filament\Standard\Pages\DownloadHistory` page (auto-discovered, no manual panel registration) renders that query through Filament's `EmbeddedTable` — no custom Blade view, no form, no tabs. The video column and a record action both link to the existing `VideoResource` "view" page.

**Tech Stack:** Laravel, Filament v4 (Standard panel, `App\Filament\Standard\*`), PHPUnit (`DatabaseTestCase`, Livewire testing helpers), MySQL via Docker test runner.

## Global Constraints

- One row per download event (not one row per video) — `Download` model is the query base, not `Assignment`.
- Table sorted reverse-chronologically by `downloaded_at` (`defaultSort('downloaded_at', 'desc')`).
- Only the channel **name** is shown — no other channel details, no link on the channel column.
- The video name is a clickable link to the video's entry under `VideoResource` ("view" page), **plus** a separate, explicit record action button that goes to the same URL.
- No filters, no search on this table.
- Page must appear in the navigation group `nav.media` ("Meine Inhalte"), **below** "Videos" — achieved via an explicit `navigationSort` on the new page (`VideoResource` has no `navigationSort`, whose Filament default is `-1`; any value `> -1` sorts after it).
- New, standalone translation files `lang/de/download_history.php` and `lang/en/download_history.php` (not nested inside `filament.php`).
- Per ADR 0003 (Rule 1 + Rule 2): the Filament page must not construct the query itself — it delegates to `DownloadRepository`.
- Per ADR 0005: the new repository method gets a method-level PHPDoc explaining the scoping contract; FQCNs are referenced via `use` imports, not inline.
- Per ADR 0002: the page class is named `DownloadHistory`, following Filament's own page-naming convention (like `MyOffers`, `ChannelApplication`) — no artificial suffix.
- Tests follow existing layering: Eloquent/query-scoping behavior in `tests/Integration/`, Livewire-rendered page behavior in `tests/Feature/`.

Reference spec: `docs/superpowers/specs/2026-08-15-download-history-page-design.md`

---

## Task 1: `DownloadRepository::forUser()`

**Files:**
- Modify: `app/Repository/DownloadRepository.php`
- Test: `tests/Integration/Repository/DownloadRepositoryTest.php`

**Interfaces:**
- Produces: `DownloadRepository::forUser(User $user): Builder` — an unexecuted Eloquent query builder for `Download` records whose assignment's video contains at least one clip created by `$user`, eager-loading `assignment.video` and `assignment.channel`. Later tasks call this via `app(DownloadRepository::class)->forUser($user)`.

- [ ] **Step 1: Write the failing tests**

Add these two test methods to the existing `DownloadRepositoryTest` class (keep the existing tests and `use` imports; add `use App\Models\User;` to the imports at the top of the file):

```php
public function testForUserReturnsOnlyDownloadsOfVideosWithUsersClips(): void
{
    $user = User::factory()->create();
    $video = Video::factory()->withClips(1, $user)->create();
    $assignment = Assignment::factory()->for($video, 'video')->create();
    $download = Download::factory()->forAssignment($assignment)->create();

    $ids = $this->repository->forUser($user)->pluck('id');

    $this->assertContains($download->getKey(), $ids->all());
    $this->assertCount(1, $ids);
}

public function testForUserExcludesDownloadsOfOtherUsersVideos(): void
{
    $user = User::factory()->create();
    $otherVideo = Video::factory()->withClips(1)->create();
    $otherAssignment = Assignment::factory()->for($otherVideo, 'video')->create();
    Download::factory()->forAssignment($otherAssignment)->create();

    $ids = $this->repository->forUser($user)->pluck('id');

    $this->assertCount(0, $ids);
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=DownloadRepositoryTest`

Expected: FAIL with `Call to undefined method App\Repository\DownloadRepository::forUser()`

- [ ] **Step 3: Implement `forUser()`**

In `app/Repository/DownloadRepository.php`, add the `App\Models\User` import and the new method:

```php
use App\Enum\StatusEnum;
use App\Models\Download;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DownloadRepository
{
    /**
     * Downloads of videos that contain at least one clip created by the given user, with the
     * owning assignment's video and channel eager-loaded.
     */
    public function forUser(User $user): Builder
    {
        return Download::query()
            ->whereHas('assignment', fn (Builder $query) => $query->hasUsersClips($user))
            ->with(['assignment.video', 'assignment.channel']);
    }

    public function latestPerVideo(): Builder
    {
        // ... existing method unchanged
```

(Leave the rest of the file — `latestPerVideo()` and `fetchDownloadedVideoIds()` — unchanged.)

- [ ] **Step 4: Run the tests to verify they pass**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=DownloadRepositoryTest`

Expected: PASS (all methods, including the two new ones and the four pre-existing ones)

- [ ] **Step 5: Commit**

```bash
git add app/Repository/DownloadRepository.php tests/Integration/Repository/DownloadRepositoryTest.php
git commit -m "feat(download-repository): add forUser query scoped to a user's videos

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Task 2: `DownloadHistory` Filament page + translations

**Files:**
- Create: `lang/de/download_history.php`
- Create: `lang/en/download_history.php`
- Create: `app/Filament/Standard/Pages/DownloadHistory.php`
- Test: `tests/Feature/Filament/Standard/Pages/DownloadHistoryTest.php`

**Interfaces:**
- Consumes: `DownloadRepository::forUser(User $user): Builder` (Task 1); `App\Filament\Standard\Resources\VideoResource::getUrl(string $name, array $parameters): string` (existing).
- Produces: `App\Filament\Standard\Pages\DownloadHistory` — a Filament page implementing `HasTable`, auto-discovered by the Standard panel, navigation group `nav.media`, `navigationSort = 1`. Later tasks (Task 3) build its table via `$page->table(Table::make($page))` and look up the column named `assignment.video.original_name` and the record action named `view-video`.

- [ ] **Step 1: Write the translation files**

`lang/de/download_history.php`:

```php
<?php

declare(strict_types=1);

return [
    'title' => 'Download-Verlauf',
    'navigation_label' => 'Download-Verlauf',
    'table' => [
        'columns' => [
            'video' => 'Video',
            'channel' => 'Kanal',
            'downloaded_at' => 'Heruntergeladen am',
        ],
        'actions' => [
            'view_video' => 'Video ansehen',
        ],
        'empty_state' => [
            'heading' => 'Noch keine Downloads',
            'description' => 'Sobald ein Kanal eines deiner Videos herunterlädt, erscheint es hier.',
        ],
    ],
];
```

`lang/en/download_history.php`:

```php
<?php

declare(strict_types=1);

return [
    'title' => 'Download History',
    'navigation_label' => 'Download History',
    'table' => [
        'columns' => [
            'video' => 'Video',
            'channel' => 'Channel',
            'downloaded_at' => 'Downloaded at',
        ],
        'actions' => [
            'view_video' => 'View video',
        ],
        'empty_state' => [
            'heading' => 'No downloads yet',
            'description' => 'Once a channel downloads one of your videos, it will show up here.',
        ],
    ],
];
```

- [ ] **Step 2: Write the failing feature test**

Create `tests/Feature/Filament/Standard/Pages/DownloadHistoryTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Pages\DownloadHistory;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Download;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class DownloadHistoryTest extends DatabaseTestCase
{
    private User $user;

    private Team $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()
            ->withOwnTeam()
            ->admin(GuardEnum::STANDARD)
            ->create();

        $this->tenant = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($this->tenant, true);
        Filament::auth()->login($this->user);
        $this->actingAs($this->user, GuardEnum::STANDARD->value);
    }

    public function testShowsOnlyDownloadsOfAuthenticatedUsersVideos(): void
    {
        $ownVideo = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create();
        $ownAssignment = Assignment::factory()->forVideo($ownVideo)->create();
        $ownDownload = Download::factory()->forAssignment($ownAssignment)->create();

        $otherVideo = Video::factory()->withClips(1)->create();
        $otherAssignment = Assignment::factory()->forVideo($otherVideo)->create();
        $otherDownload = Download::factory()->forAssignment($otherAssignment)->create();

        Livewire::test(DownloadHistory::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$ownDownload])
            ->assertCanNotSeeTableRecords([$otherDownload]);
    }

    public function testTableIsSortedByDownloadedAtDescending(): void
    {
        $video = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create();

        $olderAssignment = Assignment::factory()->forVideo($video)->create();
        $olderDownload = Download::factory()
            ->forAssignment($olderAssignment)
            ->at(now()->subDays(2))
            ->create();

        $newerAssignment = Assignment::factory()->forVideo($video)->create();
        $newerDownload = Download::factory()
            ->forAssignment($newerAssignment)
            ->at(now())
            ->create();

        Livewire::test(DownloadHistory::class)
            ->assertCanSeeTableRecords([$newerDownload, $olderDownload], inOrder: true);
    }

    public function testShowsChannelNameThatDownloadedTheVideo(): void
    {
        $channel = Channel::factory()->create(['name' => 'Awesome Channel']);
        $video = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create();
        $assignment = Assignment::factory()->forVideo($video)->forChannel($channel)->create();
        Download::factory()->forAssignment($assignment)->create();

        Livewire::test(DownloadHistory::class)
            ->assertSee('Awesome Channel');
    }

    public function testShowsEmptyStateWhenNoDownloadsExist(): void
    {
        Livewire::test(DownloadHistory::class)
            ->assertSee(__('download_history.table.empty_state.heading'));
    }

    public function testNavigationTextsUseTranslations(): void
    {
        self::assertSame(
            __('download_history.title'),
            (new DownloadHistory())->getTitle()
        );
        self::assertSame(
            __('download_history.navigation_label'),
            DownloadHistory::getNavigationLabel()
        );
        self::assertSame(
            __('nav.media'),
            DownloadHistory::getNavigationGroup()
        );
    }
}
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=DownloadHistoryTest`

Expected: FAIL with `Class "App\Filament\Standard\Pages\DownloadHistory" not found`

- [ ] **Step 4: Implement the page**

Create `app/Filament/Standard/Pages/DownloadHistory.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages;

use App\Filament\Standard\Resources\VideoResource;
use App\Models\Download;
use App\Repository\DownloadRepository;
use Auth;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class DownloadHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'nav.media';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('download_history.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __(static::$navigationGroup);
    }

    public function getTitle(): string
    {
        return __('download_history.title');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => app(DownloadRepository::class)->forUser(Auth::user()))
            ->defaultSort('downloaded_at', 'desc')
            ->columns([
                TextColumn::make('assignment.video.original_name')
                    ->label(__('download_history.table.columns.video'))
                    ->url(fn (Download $record) => VideoResource::getUrl(
                        'view',
                        ['record' => $record->assignment->video]
                    ))
                    ->limit(60),
                TextColumn::make('assignment.channel.name')
                    ->label(__('download_history.table.columns.channel')),
                TextColumn::make('downloaded_at')
                    ->label(__('download_history.table.columns.downloaded_at'))
                    ->dateTime('d.m.Y, H:i')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('view-video')
                    ->label(__('download_history.table.actions.view_video'))
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->button()
                    ->url(fn (Download $record) => VideoResource::getUrl(
                        'view',
                        ['record' => $record->assignment->video]
                    )),
            ])
            ->toolbarActions([])
            ->emptyStateHeading(__('download_history.table.empty_state.heading'))
            ->emptyStateDescription(__('download_history.table.empty_state.description'));
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test --filter=DownloadHistoryTest`

Expected: PASS (all five methods)

- [ ] **Step 6: Commit**

```bash
git add lang/de/download_history.php lang/en/download_history.php \
  app/Filament/Standard/Pages/DownloadHistory.php \
  tests/Feature/Filament/Standard/Pages/DownloadHistoryTest.php
git commit -m "feat(standard-panel): add download history page under Meine Inhalte

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Task 3: Verify video link and record action point at the correct video

**Files:**
- Create: `tests/Integration/Filament/Standard/Pages/DownloadHistoryTest.php`

**Interfaces:**
- Consumes: `App\Filament\Standard\Pages\DownloadHistory` (Task 2), `App\Filament\Standard\Resources\VideoResource::getUrl()` (existing).

- [ ] **Step 1: Write the failing test**

Create `tests/Integration/Filament/Standard/Pages/DownloadHistoryTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Pages\DownloadHistory;
use App\Filament\Standard\Resources\VideoResource;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Download;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Tests\DatabaseTestCase;

final class DownloadHistoryTest extends DatabaseTestCase
{
    private User $user;

    private Team $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()
            ->withOwnTeam()
            ->admin(GuardEnum::STANDARD)
            ->create();

        $this->tenant = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($this->tenant, true);
        Filament::auth()->login($this->user);
        $this->actingAs($this->user, GuardEnum::STANDARD->value);
    }

    public function testVideoColumnLinksToVideoViewPage(): void
    {
        [$video, $download] = $this->createDownload();

        $page = app(DownloadHistory::class);
        $table = $page->table(Table::make($page));

        $column = $table->getColumn('assignment.video.original_name');
        $column->record($download);

        $expected = VideoResource::getUrl('view', ['record' => $video]);

        self::assertSame($expected, $column->getUrl());
    }

    public function testViewVideoActionLinksToVideoViewPage(): void
    {
        [$video, $download] = $this->createDownload();

        $page = app(DownloadHistory::class);
        $table = $page->table(Table::make($page));

        $action = $table->getFlatActions()['view-video'];
        $action->record($download);

        $expected = VideoResource::getUrl('view', ['record' => $video]);

        self::assertSame($expected, $action->getUrl());
    }

    /**
     * @return array{0: Video, 1: Download}
     */
    private function createDownload(): array
    {
        $channel = Channel::factory()->create();
        $video = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create();
        $assignment = Assignment::factory()->forVideo($video)->forChannel($channel)->create();
        $download = Download::factory()->forAssignment($assignment)->create();

        return [$video, $download];
    }
}
```

- [ ] **Step 2: Run the test**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test tests/Integration/Filament/Standard/Pages/DownloadHistoryTest.php`

Expected: PASS immediately, since `DownloadHistory::table()` was already implemented with the correct `->url()` closures in Task 2. If it fails instead, it means the column/action URL closures in `app/Filament/Standard/Pages/DownloadHistory.php` are missing or wrong — fix them to match Step 4 of Task 2 exactly, then re-run.

- [ ] **Step 3: Re-run to confirm both methods pass**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test tests/Integration/Filament/Standard/Pages/DownloadHistoryTest.php`

Expected: PASS (both methods)

- [ ] **Step 4: Run the full test suite**

Run: `docker exec dashclip-delivery-sharing-1 php artisan test`

Expected: PASS (no regressions)

- [ ] **Step 5: Commit**

```bash
git add tests/Integration/Filament/Standard/Pages/DownloadHistoryTest.php
git commit -m "test(standard-panel): verify download history links target the video view page

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Self-Review Notes

- **Spec coverage:** granularity (Task 1 + spec "one row per download"), scoping (Task 1), navigation group/icon/label/sort (Task 2), columns + record action + empty state (Task 2), sorting (Task 2 test), translations (Task 2), ADR 0003/0002/0005 compliance (Task 1 + 2), URL correctness (Task 3) — all spec sections are covered.
- **Placeholder scan:** no TBD/TODO; every step has runnable code.
- **Type consistency:** `DownloadRepository::forUser(User $user): Builder` (Task 1) is called identically in `DownloadHistory::table()` (Task 2); column name `assignment.video.original_name` and action name `view-video` (Task 2) are referenced identically in Task 3's assertions.
