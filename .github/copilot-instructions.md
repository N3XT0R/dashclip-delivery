# DashClip Delivery - AI Coding Agent Instructions

## Architecture Overview

**DashClip Delivery** is a Laravel 12 video distribution system that ingests dashcam/UGC content, deduplicates via
SHA-256, auto-assigns to multiple channels with weighted quotas, and delivers signed time-limited offer links for
downloads. It combines FFmpeg preview generation, Dropbox integration, WebSocket real-time progress (via Reverb), and
comprehensive audit logging.

### Core Data Flow

1. **Ingest**: Scan local/Dropbox folders → Extract ZIPs → Detect duplicates (SHA-256 hash stored in `videos` table) →
   Store video metadata + binary file
2. **Clip Creation**: Extract timestamp ranges from uploaded CSV, attach to videos
3. **Distribution**: Assign clips to channels using `AssignmentDistributor` with weighted round-robin (quotas, blocks,
   team-based pooling)
4. **Offer & Download**: Generate signed links, track downloads/returns with full audit trail in `assignments` table
5. **Cleanup**: Expire old offers, mark downloads as returned, optionally delete expired files

### Key Tables & Relationships

- **videos**: Store file hash, duration, ingestion metadata
- **clips**: Extracted segments (start_sec, end_sec) + user submission info
- **channels**: Recipients with pausing, quotas, blocking rules
- **assignments**: Distribution state (status, expires_at, download_token)
- **downloads**: Track user interactions (clicked, downloaded, returned)
- **activity_log**: Spatie audit trail for all model changes

## Critical Developer Workflows

### Essential Commands

```bash
php artisan ingest:scan          # Ingest new videos (dedupes via SHA-256)
php artisan assign:distribute     # Assign videos to channels (weighted quotas)
php artisan notify:offers         # Send offer emails with signed links
php artisan notify:reminders      # Remind about expiring offers
php artisan assign:expire         # Mark expired assignments, block channels
php artisan weekly:run            # Run expire → distribute → notify in sequence
php artisan dropbox:refresh-token # Refresh Dropbox OAuth token

# Development
composer test                 # Run all test suites (Unit, Feature, Integration)
npm run build               # Compile Vite assets (Tailwind + custom CSS)
npm run dev                 # Watch mode for development
```

### Testing Structure

- **tests/Unit**: Pure logic testing (services, repositories)
- **tests/Feature**: HTTP & blade testing
- **tests/Integration**: End-to-end flows (ingest → distribute → download)
- **Configuration**: `phpunit.xml` excludes WeeklyRun command and Policies; uses in-memory cache/SQLite for speed

## Project-Specific Patterns

### 1. Strict Types + Type Declarations

All PHP files require `declare(strict_types=1)` at top. Use full type hints on methods:

```php
public function distribute(?int $quotaOverride = null): array
public function handle(IngestContext $context): IngestResult
```

### 2. Repository Pattern + DTOs

- **Repositories** (e.g., `AssignmentRepository`, `VideoRepository`) encapsulate data access
- **DTOs** (e.g., `ChannelPoolDto`, `UploaderPoolInfo`) transport immutable data between services
- **Contracts** in `app/Repository/Contracts/` and `app/Services/Contracts/` define interfaces

### 3. Service Layer Organization

- `Services/` contains domain logic (distribution, ingest, offer management)
- `Services/Ingest/` has pipeline architecture: `IngestPipeline` + step classes
- `Services/Mail/` handles scanning, notifying, logging
- `Services/Dropbox/` manages OAuth + auto-refresh token provider

### 4. Facade Pattern

Custom facades for convenience:

- `Cfg` (ConfigService) → database-backed configuration
- `DynamicStorage` → Switch between local/S3/Dropbox storage
- `PathBuilder` → Generate consistent file paths
- `NotificationDiscovery` → Auto-find notification classes

### 5. Jobs & Queuing

- `ProcessUploadedVideo`: Extract uploaded file metadata
- `BuildZipJob`: Async ZIP building with WebSocket progress (Reverb) for UI feedback
- Queue driver: `database` (fallback to sync in testing)

### 6. Filament Admin

- `app/Filament/Resources/`: CRUD pages for channels, assignments, users
- `app/Filament/Pages/`: Custom dashboard pages
- `app/Filament/Support/`: UI helpers, form builders
- Shield integration for role-based permissions (channels see only their assignments)

### 7. Enum-Driven State

Use enums for finite states:

- `StatusEnum`: new, assigned, clicked, downloaded, returned, expired
- `BatchTypeEnum`: ASSIGN, INGEST, CLEANUP
- `DownloadStatusEnum`: pending, in_progress, completed, failed

### 8. Activity Logging

Spatie `laravel-activitylog` tracks all model changes:

- `Video`, `Clip`, `Assignment`, `Download` models log automatically
- Queries: `Activity::where('subject_type', Video::class)...`

## Integration Points & External Dependencies

### Dropbox Integration

- OAuth2 flow via `DropboxController` (connect → callback → token store)
- `AutoRefreshTokenProvider`: Automatically refreshes expired tokens before API calls
- Flysystem adapter in `DynamicStorageService` for file operations
- Token stored in `users.dropbox_token` as encrypted JSON

### FFmpeg Preview Generation

- `pbmedia/laravel-ffmpeg` generates MP4 previews on ingest
- Output stored in `storage/app/previews/` (configurable path)
- Configured in `config/laravel-ffmpeg.php`

### Email & IMAP Scanning

- `webklex/laravel-imap`: Scan reply/bounce mailbox
- `MailReplyScanner` parses inbound mails → `ReplyHandler`, `BounceHandler`, `InboundHandler`
- Mail logs tracked in `MailLog` model via `MailHistory` observer

### WebSockets (Reverb)

- Real-time ZIP progress: `BuildZipJob` broadcasts `ZipProgressBroadcast` event
- Client listens on channel: `private-zip-{batch_id}`
- Config in `config/reverb.php` and `.env` (`REVERB_*`)

### Storage Backends

- Abstracted via `DynamicStorageService` + `Facades/DynamicStorage`
- Supports: local filesystem, AWS S3, Dropbox
- Configured in `config/filesystems.php` + environment variables

## Conventions & Naming

- **Models**: Singular, CamelCase (`Video`, `Clip`, `Channel`, `Assignment`)
- **Tables**: Plural, snake_case (`videos`, `clips`, `channels`, `assignments`)
- **Services**: Suffix with `Service` or `Distributor` / `Expirer` / `Notifier`
- **DTOs**: Suffix with `Dto` or `Info` (e.g., `ChannelPoolDto`, `UploaderPoolInfo`)
- **Enums**: Suffix with `Enum` (e.g., `StatusEnum`, `BatchTypeEnum`)
- **Exceptions**: Custom exceptions in `app/Exceptions/`
- **Localization**: German default (`config/app.php`: `APP_LOCALE=de`), fallback to English

## Testing Best Practices

### General Test Setup

1. Use factories from `database/factories/` for model creation
2. Mock external dependencies (Dropbox, Dropbox token refresh, FFmpeg)
3. Assert model state changes via `Activity::query()` + `assertDatabaseHas()`
4. Use in-memory SQLite for speed: `phpunit.xml` configures this
5. Disable real mail in tests: `MAIL_MAILER=log`

### Filament Page/Resource Testing (Critical)

When testing **Filament Standard pages** (e.g., `MyOffers`, `ChannelApplication`), follow this setup:

```php
use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;

protected function setUp(): void
{
    parent::setUp();

    // 1. Create user with own team (MUST use withOwnTeam())
    $this->user = User::factory()
        ->withOwnTeam()
        ->create();
    
    // 2. Get default team via TeamRepository
    $this->team = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

    // 3. Configure Filament BEFORE acting as user
    Filament::setCurrentPanel(PanelEnum::STANDARD->value);
    Filament::setTenant($this->team, true);
    Filament::auth()->login($this->user);

    // 4. Act as the user
    $this->actingAs($this->user, GuardEnum::STANDARD->value);
    
    // 5. Grant required permissions (NOT via Policy, via Permission)
    $this->grantPagePermissions();
}

private function grantPagePermissions(): void
{
    // Find or create custom permission (e.g., "ViewMyOffers")
    Permission::findOrCreate('ViewMyOffers', GuardEnum::STANDARD->value);
    $this->user->givePermissionTo('ViewMyOffers');
}
```

**Key Points:**

- **Always** use `withOwnTeam()` factory modifier for users in Standard panel
- **Always** call `Filament::setCurrentPanel()`, `setTenant()`, and `auth()->login()` BEFORE `actingAs()`
- **Never** use deprecated methods like `setDefaultTeam()` (doesn't exist in v4)
- Grant **permissions** directly via `givePermissionTo()`, not via policies for pages
- Use `GuardEnum::STANDARD->value` as the guard name for Standard panel
- Use `PanelEnum::STANDARD->value` for panel configuration

### Shield Integration

Filament Shield auto-discovers pages. Custom pages need:

- Trait: `use HasPageShield { canAccess as canAccessShield; }` (optional, for manual gates)
- Permission: Created automatically if `config/filament-shield.php` has `'discover_all_pages' => true`
- This project uses Shield, so pages are auto-protected

## Common Pitfalls

- **Tenant Not Set**: 403 errors come from missing `Filament::setTenant()` in Feature tests
- **Wrong User Factory**: Not using `withOwnTeam()` → user has no teams → access denied
- **Deprecated Methods**: `setDefaultTeam()` doesn't exist; use `->teams()->first()` instead
- **SHA-256 Deduplication**: Always check existing video by hash before storing (prevents duplicates across
  teams/uploaders)
- **Quota Management**: `AssignmentDistributor` pools videos by team/uploader first, then distributes fairly—understand
  `UploaderPoolInfo` structure
- **Signed URLs**: Links include encrypted `download_token` + expiration; validate token before serving downloads
- **Channel Blocking**: Expired assignments auto-block channels temporarily (see `AssignmentExpirer`)
- **Storage Path Consistency**: Use `PathBuilderService` for all file paths—hardcoding breaks portability

## Environment Setup

### PHP & Extensions (Required)

- **PHP 8.4** minimum (Laravel 12 requirement)
- **Extensions**: `ext-intl`, `ext-zip`, `ext-dom`, `ext-curl`, `ext-xml`, `ext-mbstring`, `ext-sqlite3`
- **Setup**: Use `php8.4` binary explicitly; some systems have multiple PHP versions installed
- **Example**: `export PATH="/usr/bin:$PATH" && php --version` (should show 8.4.x)

### Composer & Dependencies

```bash
composer install                # Install all dependencies
composer test                   # Run all tests with coverage
./vendor/bin/phpunit --no-coverage  # Run tests without coverage (faster for CI)
```

## Key Files to Reference

- [app/Services/AssignmentDistributor.php](app/Services/AssignmentDistributor.php) - Distribution logic with weighted
  quotas
- [app/Services/Ingest/IngestPipeline.php](app/Services/Ingest/IngestPipeline.php) - Ingest architecture & pipeline
  pattern
- [app/Console/Commands/WeeklyRun.php](app/Console/Commands/WeeklyRun.php) - Orchestrates expire → distribute → notify
- [app/Models/Assignment.php](app/Models/Assignment.php) - Core assignment state & relationships
- [app/Facades/DynamicStorage.php](app/Facades/DynamicStorage.php) - Storage abstraction
- [routes/web.php](routes/web.php) - Public offer & download routes
- [database/migrations/](database/migrations/) - Schema definitions
- [tests/Feature/Filament/Standard/Resources/VideoResourceTest.php](tests/Feature/Filament/Standard/Resources/VideoResourceTest.php) -
  Example of correct Filament Standard test setup
- [tests/Feature/Pages/MyOffersPageTest.php](tests/Feature/Pages/MyOffersPageTest.php) - MyOffers page tests with
  Filament setup
