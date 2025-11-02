# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Enhanced Logging & Observability**
    - Introduced extended activity and event logging across key application components,  
      improving traceability, auditability, and operational transparency.  
      The new logging layer provides consistent context propagation and structured output,  
      ensuring better insight into background processes and system-level events.
    - This foundation enables unified monitoring across synchronous and asynchronous workflows,  
      preparing the application for future real-time observability integrations and analytics.

### Fixed

- **Video Upload Time Field Behavior**
    - Resolved an issue where the `start_sec` and `end_sec` inputs were editable before a video was uploaded,  
      leading to inconsistent or invalid timing data.
    - The inputs are now automatically disabled until a valid duration is detected and populated via Livewire.  
      Once the video metadata is processed, `end_sec` is prefilled based on the actual video duration.
    - Simplified frontend logic by removing redundant DOM manipulation for `end_sec`;  
      updates are now handled fully through reactive Filament state changes.

## [3.0.0-beta.2] - 2025-11-01

### Fixed

- **FFmpeg Preview Generation & Configuration**
    - Fixed a `PreviewGenerationException` that occurred when FFmpeg configuration parameters  
      (`ffmpeg_video_args`) were persisted as associative arrays instead of encoded JSON.  
      The configuration migration now serializes arrays before storage via `Cfg::set()`, preventing  
      MySQL `Unknown column` errors and ensuring correct behavior with the `json` cast type.
    - Improved argument handling in `PreviewService::ffmpegParams()` by introducing consistent key/value  
      normalization. Both indexed and associative parameter lists are now reliably converted into  
      valid FFmpeg CLI arguments (e.g. `-vf scale=trunc(iw/2)*2:trunc(ih/2)*2 -crf 30`), ensuring  
      predictable encoding behavior across environments.
    - Refined default encoding parameters (`-crf`, `-preset`, and `-vf scale`) to reduce preview  
      file size while maintaining acceptable visual quality.  
      Previous defaults produced oversized previews (up to 60–70 MB), leading to high I/O load and  
      degraded server responsiveness during generation.  
      Controlled compression now ensures that previews remain compact and streamable,  
      even under constrained VPS conditions.
    - These optimizations also resolve a systemic performance issue where oversized previews  
      could saturate PHP-FPM or Apache worker threads, blocking concurrent requests.  
      By lowering per-preview CPU and disk overhead, the application now sustains  
      smooth parallel generation without increasing hardware resources.

## [3.0.0-beta.1] - 2025-11-01

### Added

- **User Model Integration Tests**
    - Added comprehensive **integration tests** for the `User` model to ensure correct behavior of  
      authentication- and identity-related functionality.
    - Covered the following areas:
        - `display_name` accessor resolution with and without `submitted_name`.
        - Persistence and retrieval of encrypted fields  
          (`app_authentication_secret`, `app_authentication_recovery_codes`).
        - Toggle behavior and persistence of `has_email_authentication`.
        - Filament panel access validation via `canAccessPanel()`.
    - Strengthened overall reliability of user authentication logic and ensured  
      compatibility with Filament’s multi-factor authentication system.

- **DropboxUploadService Integration Tests & Refactoring**
    - Added comprehensive **integration tests** for `DropboxUploadService`, enabling full offline validation  
      of upload logic without external API calls.
    - Covered the following scenarios:
        - Direct upload for small files below chunk threshold.
        - Chunked uploads with session start, multiple appends, and finalization.
        - Progress bar interaction and proper stream handling.
        - Error logging and exception resilience.
    - Refactored `DropboxUploadService` to support **dependency-injected DropboxClient**,  
      allowing reliable mocking and isolation during tests.
    - Strengthened code reliability and maintainability of the Dropbox upload workflow.

- **Feature & Integration Tests for Filament Resources**
    - Introduced comprehensive test coverage for all primary **Filament v4 resources** to ensure stability and
      consistency across administrative interfaces.
    - Tests leverage **Livewire assertions** (`assertCanSeeTableRecords`, `assertTableActionVisible`, etc.)  
      to confirm correct UI behavior in Filament v4.
    - Improves confidence in CRUD functionality and internal access control logic across the Filament admin panel.

### Fixed

- **Video Model**
    - Fixed a `TypeError` in `getPreviewPath()` that occurred when no clip preview existed.  
      Added a null check before verifying file existence on the storage disk to ensure  
      safe and predictable behavior when preview files are missing.

- **Dropbox Upload Stability**
    - Resolved `lookup_failed/incorrect_offset` errors during large file uploads to Dropbox.  
      Ensured reliable chunked upload behavior by introducing explicit stream rewinding and  
      byte-based end-of-file detection instead of relying on `feof()`.
    - Improved robustness of `DropboxUploadService` by handling edge cases for empty files,  
      accurate session completion (`uploadSessionFinish`), and safe cleanup of read streams.  
      This fix guarantees stable resumable uploads and prevents incomplete session errors  
      in high-latency or multi-chunk scenarios.

- **User Model – Email Authentication TypeError**
    - Fixed a `TypeError` in `App\Models\User::hasEmailAuthentication()` where the method could return `null`  
      instead of a strict boolean.  
      This occurred when the `has_email_authentication` attribute was unset or `NULL` in the database, causing  
      Filament’s Multi-Factor Authentication system to throw a type violation during profile or login operations.
    - The method now reliably returns a boolean via a null-safe cast (`($this->has_email_authentication ?? false)`),  
      ensuring strict type compliance with the `HasEmailAuthentication` contract and preventing runtime errors  
      in both Filament v4 UI interactions and automated test contexts.
    - Improves overall stability of the Filament MFA integration and guarantees deterministic behavior even for  
      uninitialized user records created through legacy migrations or factories.

- **AssignmentRelationManager:**  
  Fixed incorrect namespace declarations that caused autoloading and test failures within the Filament resource
  structure.  
  The class now resides under the correct namespace `App\Filament\Resources\Batches\RelationManagers`, fully aligned
  with Filament v4 conventions.

## [3.0.0-alpha.3] - 2025-10-31

### Added

- **Ingest System Stabilization**
    - Added comprehensive **feature and integration tests** for the `ingest:scan` command to ensure consistent file
      handling, duplicate detection, and batch statistics integrity.
    - Introduced fixture-based test structure using real video samples to validate the complete ingest workflow
      end-to-end.
    - Improved test coverage for concurrent ingest lock handling to prevent race conditions.
    - Added verification of file deletion and content-addressed video storage consistency.
    - Strengthened overall reliability and maintainability through systematic test-driven validation of ingest
      processes.

- **Mail Handling Stability**
    - Added comprehensive **unit tests** for `InboundHandler` to ensure reliable inbound message parsing and repository
      integration.
    - Added coverage for subject parsing, header extraction, and duplicate-mail detection to prevent re-processing of
      previously handled messages.
    - Implemented mocking of Webklex IMAP message flow (`Header`, `Attribute`, and `Message` chains) to validate correct
      handling without external dependencies.
    - Improved overall mail ingestion reliability through precise, type-safe test validation of handler logic and log
      events.

- **Mail Logging Reliability**
    - Added a dedicated **integration test** for the `LogSentMail` listener to verify creation of `MailLog` entries
      after
      successful mail dispatch through Laravel’s `array` mailer.
    - Ensures message headers, subject, recipient, and HTML content are persisted correctly in the database.
    - Confirms automatic generation of UUID-based internal IDs and RFC-compliant message identifiers.
    - Validated full compatibility with Laravel 12’s Mailable and Event system (no deprecated `build()` or
      `SentMessage` usage).

- **Channel Welcome Mail Refactor**
    - Introduced a fully refactored **`SendChannelWelcomeMailCommand`** with clear separation of responsibilities
      between
      command, service, and repository layers.
    - Added a new **`ChannelService`** to encapsulate all business logic related to channel approval and welcome mail
      distribution.
    - Introduced a dedicated **`ChannelRepository`** providing reusable data-access methods (`getActiveChannels`,
      `getPendingApproval`, `findById`, `findByEmail`).
    - Implemented dependency injection across command and service for cleaner, testable architecture.
    - Retained all German CLI output to ensure backward compatibility with existing feature tests.

- **Channel Command & Repository Tests**
    - Added extensive **feature tests** for the `channels:send-welcome` command covering dry-run mode, targeted sending
      by
      ID or email, `--force` behavior, and mail dispatch validation.
    - Introduced **integration tests** for the new `ChannelService`, verifying channel preparation, approval token
      validation, eligibility selection, and mail delivery.
    - Added **repository-level integration tests** for all query methods, ensuring correct filtering, ordering, and
      handling of null/invalid lookups.
    - Ensured comprehensive mail testing using `Mail::fake()` and queue assertions to validate real send behavior.
    - Improved overall maintainability and reliability of the channel mail system through isolated, layered test
      coverage.

- **Mail Handling Stability**
    - Added comprehensive **integration tests** for the listeners  
      `SendChannelCreatedNotification` and `SendWelcomeMail`,  
      ensuring correct triggering and welcome mail dispatch behavior.
    - Tests cover both direct listener invocation and automatic event registration  
      via the Laravel event dispatcher.
    - Includes scenarios with and without valid email addresses, verifying queue behavior  
      (`Mail::queue` vs. `Mail::send`) for stable runtime handling.
    - Unified use of `Mail::fake()` across all listener tests for consistent, type-safe mail verification.

- **Clip Ownership Preparation**
    - Added a new nullable `user_id` column to the **`clips`** table to establish a future link between clips and their
      uploaders.
    - Extended the **`Clip`** model with a `user()` relationship and a new `setUser()` helper for consistent assignment
      of both `user_id` and `submitted_by` attributes.
    - Updated the **`ProcessUploadedVideo`** job to automatically associate newly created clips with the uploading user,
      preserving backward compatibility with the existing `submitted_by` field.
    - Implemented the migration as part of a gradual transition towards full **user-based ownership** of clips, allowing
      mixed legacy and new data during rollout.

### Changed

- **User Profile Uniqueness**
    - Enforced uniqueness for both `name` and `submitted_by` fields within user profiles to prevent duplicate
      identifiers.
    - This change ensures a one-to-one mapping between profile names and submission identities, improving data
      reliability across clip attribution, audit logs, and activity tracking.
    - Existing data remains unaffected; uniqueness is applied only to new or updated records going forward.

### Fixed

- **Mail Handling Robustness**
    - Improved `SendWelcomeMail` listener resilience by adding internal `try/catch` handling  
      to prevent crashes when Mail queue dispatch fails.
    - Prevented runtime exceptions during queued mail operations,  
      ensuring graceful degradation and consistent event processing under failure conditions.

- **Clip Attribution Consistency**
    - Fixed an issue where newly created clips were not using the authenticated user’s `display_name` for the
      `submitted_by` field.
    - The `ProcessUploadedVideo` job now consistently delegates ownership handling to the `Clip::setUser()` method,
      ensuring that both `user_id` and `submitted_by` are aligned.
    - This resolves inconsistent attribution between stored clips and their actual uploader identity, improving audit
      trail accuracy and data integrity.

- **Footer Rendering in Filament Panels**
    - Fixed an issue where the global footer would overlap or shift upward on pages containing file upload components.
    - The footer is now **excluded** from the Filament video upload page to prevent layout distortion caused by nested
      scroll containers.
    - This ensures stable layout behavior during video uploads while keeping consistent footer rendering across all
      other admin views.

## [3.0.0-alpha.2] - 2025-10-31

> **Note:** This is the first production deployment of the new 3.x architecture.  
> It represents a major structural and functional refactor compared to 2.5.0,  
> introducing a unified ingestion pipeline, real-time ZIP download system,  
> and a GDPR-compliant channel approval & communication flow.  
> Existing channels and assignments remain compatible.

### Added

- **Offer Link Tracking**
    - Introduced a new `offer_link_clicks` table to record when a channel opens the offer overview page.
    - Each valid (signed) request to `OfferController::show()` now creates a record containing:
        - References to `batch_id` and `channel_id`.
        - Timestamp of access (`clicked_at`).
        - Optional `user_agent` for basic analytics.
    - Multiple visits by the same channel are counted individually to reflect real viewing behavior.
    - Added a Filament admin resource **Offer Link Clicks** under *Statistics*:
        - Displays all recorded clicks with batch and channel relations.
        - Fully sortable by date and searchable by channel name.
    - Tracking is only performed for valid signed links (`ensureValidSignature()`).
    - Existing download tracking remains unchanged.

- **User Welcome Email System**
    - Introduced a fully automated welcome email workflow for newly created users.
    - Added `UserCreated` event and corresponding `SendWelcomeMail` listener.
    - Implemented `UserWelcomeMail` Mailable, supporting both self-registrations and backend-created accounts.
    - When users are created via the Filament admin panel:
        - A random password is automatically generated if none is provided.
        - The plaintext password is included in the welcome email (only for backend-created users).
    - Emails use contextual templates based on creation source:
        - **Frontend users:** Standard welcome message.
        - **Backend-created users:** Account access details included.
    - Added localized subject lines and structured Blade email layout with footer links (Impressum, Datenschutz,
      Nutzungsbedingungen, etc.).
    - All emails are logged and sent asynchronously through Laravel’s mail queue system.
    - Security-conscious handling ensures that plaintext passwords are **never stored** in the database — only
      transmitted once via the initial welcome email.

### Changed

- **Download System**
    - The ZIP downloader now supports **single video downloads** through the unified `ZipDownloader` workflow.  
      Instead of triggering a direct file link, the “Download Single” button now invokes the same process used  
      for batch downloads — including the real-time progress modal, WebSocket (Echo) events, and unified logging.
    - Consolidated logic for single and multiple downloads via the common `startDownload()` method.
    - Added optional parameter handling for forced assignment IDs to enable clean reuse of the modal-based flow.
    - Improved code readability and reliability:
        - Consistent file name sanitization for both single and multi downloads.
        - Graceful handling of missing selections or invalid assignment states.
        - Minor structural refactoring for clarity and maintainability.

- **Offer Controller Refactor**
    - Extracted all offer preparation logic (assignment fetching, temp URL generation, ZIP link creation)
      from `OfferController` into a dedicated `OfferService`.
    - New method `OfferService::prepareOfferViewData()` centralizes data preparation for the offer view.
    - Controller now only handles request validation (`ensureValidSignature`) and delegates data loading to the service.
    - Improves separation of concerns, readability, and future testability.

### Deprecated

- **Legacy Assignment Download Controller**
    - Marked `AssignmentDownloadController` as **deprecated** in favor of the new real-time WebSocket-based download
      flow handled by `ZipController`.
    - The legacy implementation previously managed direct single-file downloads via signed URLs and stream responses.
    - All functionality has been superseded by the new zip-based asynchronous download process, which:
        - Tracks download progress via WebSocket events.
        - Supports batch and individual video downloads.
        - Provides improved error handling and download verification.
    - The old controller remains temporarily available for backward compatibility but will be removed in a future
      release.

### Removed

- **Legacy Ingest Process & Components**
    - Removed the deprecated `IngestScan` command and related legacy ingest components.
    - Functionality has been fully replaced by the new modular `IngestScanner` pipeline and supporting services.
    - The refactored ingest architecture now provides transactional safety, unified logging, and service-based
      extensibility across all entrypoints (web and CLI).

- **Legacy Preview Service & FFmpeg Components**
    - Removed all deprecated classes and helpers related to the old `PreviewService` implementation.
    - The former preview generation logic (manual FFmpeg invocation, direct filesystem access, and inline parameter
      handling)
      has been fully replaced by the new **modular preview pipeline** powered by `pbmedia/laravel-ffmpeg`.
    - Preview rendering is now configuration-driven and integrated with the `DynamicStorageService` for consistent,
      driver-agnostic file access.
    - This cleanup eliminates redundant code paths, ensures better stability, and aligns all preview operations with the
      new ingest architecture.

## [3.0.0-alpha] - 2025-10-30

### Added

- **Auth**
    - Multi-factor authentication (MFA) with support for authenticator apps (TOTP) and one-time codes via email.
    - Email verification and change verification for secure and verified user identities.
    - Automatic role assignment via `UserObserver`, assigning the default `panel_user` role to newly created
      users. ([#126](https://github.com/N3XT0R/dashclip-delivery/issues/126))

- **Admin Panel**
    - Role and permission management via Filament Shield (integrates Spatie Laravel Permission with the admin panel,
      providing a full UI for roles, permissions, and access
      control). ([#126](https://github.com/N3XT0R/dashclip-delivery/issues/126))
    - Initial roles and permissions seeder generated via `php artisan shield:seeder`, providing default admin access and
      baseline permission data. ([#126](https://github.com/N3XT0R/dashclip-delivery/issues/126))
    - Web-based video upload in the admin UI, complementing existing ingest workflows.
        - Ensures a valid preview range is available even if the user doesn't manually
          adjust the start/end time after upload.

- **Ingest & Upload Refactor** ([#152](https://github.com/N3XT0R/dashclip-delivery/issues/152))
    - Introduced a fully modular ingestion pipeline with transactional safety and storage abstraction.
    - Added `IngestResult` enum for standardized ingest return values.
    - Added `IngestStats` value object for batch statistics and aggregation.
    - Added dedicated exception classes for clearer flow control and debugging:
        - `InvalidTimeRangeException` — thrown when preview clip ranges are invalid.
        - `PreviewGenerationException` — includes contextual metadata for FFmpeg errors.
    - Added new `App\Services\Ingest\IngestScanner` (modular replacement of legacy class).
    - Added `CsvService` for isolated metadata (CSV) import.
    - Implemented unified logging and consistent exception handling across CLI and web ingest.
    - Integrated **Laravel-FFmpeg** for preview generation with dynamic codec, preset, and parameter configuration.
    - Added full database transaction handling (`DB::beginTransaction`, `commit`, `rollback`) during video processing.
    - Introduced `DynamicStorageService` and `DynamicStorage` facade for transparent, driver-agnostic file access:
        - Automatically builds a Laravel `Filesystem` instance for any given path (local, Dropbox, S3, etc.).
        - Provides recursive file listing via `listFiles()` returning `FileInfoDto` objects.
        - Replaces all direct filesystem calls (`fopen`, `unlink`, `hash_file`, etc.) with stream-safe equivalents.
        - Implements efficient hashing (`sha256`) using stream-based `hash_update_stream` for large files.
        - Enables consistent file handling across CLI and Web contexts through the same unified API layer.

- **CSV Import**
    - Introduced `ClipImportResult` value object to encapsulate import outcomes:
        - Tracks created, updated, and warning counts.
        - Optionally aggregates references to affected `Clip` models.
        - Enables downstream traceability and structured reporting for batch imports.
    - Added strong-typed result flow (`ClipImportResult` → `ImportStats`) replacing mutable array counters.
    - Added dedicated method signatures to support typed aggregation in `processRow`, `updateClipIfDirty`, and
      `createClip`.
    - Added improved error handling and warning tracking for malformed or missing data rows.
    - Added `importInfoFromDisk()` and `importFromStream()` methods for driver-agnostic import via `Filesystem`
      (compatible with `DynamicStorageService`).

- **Configuration**
    - Config table now supports a `selectable` JSON column so settings can offer predefined choices.
    - New FFMPEG configuration category seeds codec, preset and parameter defaults for preview generation.
    - Admin UI renders selectable values for JSON-based settings as multi-select inputs.

- **System Monitoring**
    - Integrated Spatie Activity Log for detailed user and system activity tracking.
    - Added ActivityResource in the System section of the admin panel for viewing, filtering, and inspecting user
      actions.
    - Each log entry records the affected model, action type, timestamp, and the responsible user (causer).
    - Activity logs include CRUD operations as well as custom system events (e.g. login, configuration changes).
    - Added observer integration for automatic logging of key model events (create, update, delete).

- **Mail Infrastructure**
    - Introduced `InboundHandler` implementing `MessageStrategyInterface` to process incoming IMAP messages.
    - Inbound mails are now stored via the central `MailRepository`, including message ID, sender, subject,
      direction (`MailDirection::INBOUND`), and status (`MailStatus::Received`).
    - Header and raw body data are preserved as structured metadata (`meta.headers`, `meta.content`).
    - Duplicate message detection prevents reprocessing based on message ID.
    - Fully RFC-conform handling of inbound timestamps and header parsing via `Carbon` conversion.
    - Logging of inbound mail events for traceability and debugging.

- **Version Service**
    - Introduced `LocalVersionService` implementing `VersionServiceInterface` for retrieving the current application
      version directly via `Composer\InstalledVersions`.  
      The service reads the root package version from the installed Composer metadata and supports an optional fallback
      callable for non-standard environments.  
      Added a new `Version` Facade providing simple access to the version across the application.

- **Channel Approval & Welcome Flow**
    - Introduced a complete opt-in workflow for newly created channels to ensure GDPR-compliant approval before video
      delivery begins.
    - Added `approved_at` column to the `channels` table for timestamped consent tracking.
    - New `ChannelApprovalController` validates approval tokens and activates channels via secure hash-based
      confirmation links.
    - Added `ChannelWelcomeMail` mailable:
        - Sends personalized welcome and approval request emails using a friendly, non-formal tone (du-form).
        - Includes a unique confirmation link (`approveUrl`) leading to a dedicated approval page.
    - Implemented new Blade views:
        - `emails/channel-welcome.blade.php` for the email layout.
        - `channels/approved.blade.php` for the confirmation page shown after approval.
    - Introduced new Artisan command `channels:send-welcome`:
        - Allows sending approval mails manually or in bulk.
        - Supports `--dry` mode (preview recipients without sending).
        - Supports `--force` mode (resend even if already approved).
    - Fully documented process with minimal data handling — no IP storage — to comply with privacy and data-minimization
      requirements.

### Fixed

- **Dropbox-Upload**
    - Fixed an issue where small files (≤ 8 MB) were not uploaded to Dropbox correctly.  
      The upload session was started but never finished because files smaller than the configured `CHUNK_SIZE`
      did not trigger the session finish step.  
      Small files are now handled via a direct `upload()` call instead of a chunked session upload.

- **Video Deletion**
    - Fixed an issue where video and preview files were not reliably deleted when removing a `Video` record.  
      Introduced a new `getPreviewPath()` method on the `Video` model to correctly resolve preview file paths,  
      including fallback to the associated `Clip` when the primary preview is missing.  
      The deletion logic in the model’s `booted()` method now ensures both original and preview files are  
      properly removed across different storage disks, with improved error handling and logging.

### Changed

- **Framework**
    - Backend upgraded to Filament v4 (UI components and pages migrated).
    - Preview generation now uses the `pbmedia/laravel-ffmpeg` package and reads all codec options from the database.

- **Ingest Architecture** ([#152](https://github.com/N3XT0R/dashclip-delivery/issues/152))
    - Replaced all direct filesystem operations (`fopen`, `unlink`, `hash_file`) with the new `DynamicStorageService`
      abstraction.
    - `DynamicStorageService` now acts as the central access layer for all file operations:
        - Transparently builds `Filesystem` adapters for local, Dropbox, or S3 disks.
        - Provides a unified API for recursive file listing, hashing, and streaming.
        - Uses efficient stream-based hashing to support very large files.
        - Ensures consistent file handling between CLI (cron) and web contexts.
    - Preview generation is now model-independent; `PreviewService` no longer depends on `Video` Eloquent models.
    - Unified code path for web uploads and CLI (cron) ingestion.
    - Clear separation of concerns:
        - `VideoService` — handles video metadata and persistence.
        - `PreviewService` — handles preview rendering.
        - `UploadService` / `DropboxUploadService` — handles upload and remote storage transfer.
    - Added full rollback safety for video creation, CSV import, and upload operations.
    - Logging unified for CLI and web contexts with improved error tracing.
      Integrated **Laravel-FFmpeg** for preview generation, replacing the previous custom FFmpeg implementation.
        - Now fully parameterized via database configuration (codec, preset, additional parameters).
        - Provides improved stability, consistent error handling, and framework-native integration.

- **InfoImporter**
    - Refactored to use `ClipImportResult` instead of primitive array-based `$stats`.
    - Replaced legacy counter passing (`&$stats`) with immutable, typed result aggregation.
    - Updated all internal pipeline methods:
        - `processRow()` now receives and updates a `ClipImportResult` instance.
        - `findVideoOrWarn()`, `updateClipIfDirty()`, and `createClip()` now contribute structured results.
    - Unified return values and removed duplicate stat calculation logic.
    - Improved naming consistency and method visibility (private helpers now focused on single responsibility).
    - Enhanced readability and maintainability by removing nested array manipulation patterns.

- **Uploads**
    - Increased maximum upload size to **1 GB** to support large video files.
    - Extended maximum upload time to **25 minutes** (≈ 5.5 Mbit/s minimum speed).
    - Optimized for real-world conditions - fully LTE-capable for mobile uploads.
    - Updated Livewire configuration (`config/livewire.php`) for smoother large uploads.

- **UI**
    - Refined layout spacing, label hierarchy, and visual alignment for improved readability.
    - Updated Filament resources and forms to align with v4 design patterns.

- **Filament v4 migration**
    - May require adjustments to custom admin pages, widgets, or themes.

- **License updated:** The project license has been changed from **MIT** to **GNU Affero General Public License v3.0 or
  later (AGPL-3.0-or-later)**  
  to ensure that all modified or network-accessible versions of *dashclip-delivery*  
  remain open source and contribute back to the community.  
  This change reflects the project's evolution from a small utility  
  to a fully featured distributed media ingestion and delivery system.

  The AGPL license provides:
    - continued freedom to use, modify, and distribute the software,
    - mandatory publication of source code for all public or private network services,
    - protection against proprietary re-use or commercialization without reciprocity.

  For more information, see the updated [LICENSE](LICENSE) file.

### Deprecated

- Legacy `App\Services\IngestScanner` class and all direct file I/O operations - replaced by the new modular ingest
  system ([#152](https://github.com/N3XT0R/dashclip-delivery/issues/152)).
- Old inline CSV import logic — replaced by `CsvService`.
- Legacy Dropbox upload implementation using direct stream operations.
- Direct filesystem access in preview and upload logic.
- Deprecated raw `import()` method now superseded by `importFromStream()` and `importInfoFromDisk()`.

### Removed

- Low-level file handling (`fopen`, `unlink`, etc.) from the ingest process.
- Tight coupling between `Video` models and preview generation.
- Legacy mail handling classes replaced by the new inbound mail processing system.
- Filament v3 dependencies and components.

- Removed mutable `$stats` arrays (`['created' => 0, 'updated' => 0, 'warnings' => 0]`) in favor of `ClipImportResult`.
- Removed inline CSV parsing logic tied to direct file handles (`fopen`, `fgetcsv`, etc.) — now delegated to
  stream-based workflow.
- Removed implicit counter manipulation inside helper methods; replaced with domain-specific result updates.

- **Configuration**
    - Version information is no longer read from a static configuration file.  
      It is now dynamically resolved from Composer metadata through the `LocalVersionService`, ensuring accurate and
      environment-independent version reporting.

## [2.5.0] - 2025-10-10

### Added

- **Automated Mail Handling**
    - New `mail:scan-replies` command checks IMAP inbox for replies and bounces.
    - Introduced `MailReplyScanner` service class using a strategy-based design for extensibility.
    - Implemented automatic bounce detection with `MailStatus::Bounced` updates and logging.
    - Added auto-reply feature: system responds to incoming replies with a FAQ mail when appropriate.
    - Auto-responses include RFC-compliant headers (`Auto-Submitted`, `X-Auto-Response-Suppress`, `Message-ID`).
    - Easter-egg header added (`X-System-Meta`) for fun and traceability.

- **Mail Infrastructure**
    - New abstract base class `AbstractLoggedMail` standardizes headers, message-ID generation, and logging.
    - Added `NoReplyFAQMail` mailable for automated system responses.
    - Refactored existing mails (`NewOfferMail`, `ReminderMail`) to use the new modern envelope/content API.
    - Introduced consistent `Message-ID` generation compliant with RFC 5322.
    - Implemented RFC 3834 conform mail classification for automated messages.
    - Created dedicated `MailLog` Filament resource, with table and detail view.

- **Admin Interface**
    - New Filament v3 resource for viewing and inspecting mail logs (status, subject, timestamps, metadata).
    - Improved visibility into sent, bounced, and replied mails.

### Changed

- Replaced outdated `tapp/filament-maillog` dependency with internal implementation compatible with Filament.
- Unified all outgoing mails under the new base class to ensure consistent logging, headers, and traceability.
- Clean separation of mail processing logic into strategy components for better maintainability.

## [2.4.0] - 2025-09-30

### Added

- New Blade component `<x-video-card>` including dedicated view for consistent video presentation.
- Extracted video card logic from overview pages into the new component.
- Display of `picked_up` assignments at the end of the overview.
- Extended test coverage:
    - OfferController tests now cover rendering and handling of `picked_up` assignments.
    - AssignmentDownloadController tests now include validation cases and a happy-path for streaming.
    - New test ensuring authenticated Filament users can bypass token validation.

### Changed

- Unified clip info layout:
    - Role, time range, and submitter are now shown in a column layout.
    - Notes are displayed in a separate row below the clip info.
    - Submitter names now appear on their own line to avoid layout breaking with longer strings.

### Fixed

- Download link validation bug:
    - Token validation now respects the case where `download_token` is only set when tracking is enabled.
    - For logged-in Filament users, token validation is skipped.

## [2.3.0] - 2025-09-28

### Added

- Added bug-report link to footer.

## [2.2.0] - 2025-09-28

### Added

- Added Roadmap to footer.

## [2.1.3] - 2025-09-27

### Security

- Updated npm dependencies to resolve vulnerabilities (1 high, 1 low).
- Ensured compatibility with `laravel-echo@2.2.0` (requires Node >=20).

## [2.1.2] - 2025-08-25

### Fixed

- Reminder notifications: now sent for assignments expiring within the next N days using a full calendar-day window.
  Previously only the exact day N was considered, causing missed reminders when the cron ran later in the day.

## [2.1.1] - 2025-08-22

### Fixed

- dont delete assignments where expire_at is null.

## [2.1.0] - 2025-08-22

### Added

- Daily `video:cleanup` command removes downloaded videos after their assignments expired for a configurable number of
  weeks.
- New setting `post_expiry_retention_weeks` controls how long downloaded videos are kept before cleanup.
- Admin downloads page lists who downloaded which video and when.
- Batch assignment table now provides a direct "Open Offer" link for each item.
- Admin login now offers a password reset option.
- Videos delete their files and preview clips from storage when they are removed.
- New batch type `remove` tracks cleanup runs.
- Channels now receive reminder emails one day before assignment links expire, listing remaining video offers and
  recording the send in a notification history.
- New setting `email_reminder_days` configures how many days in advance reminder emails are sent.
- Admin panel now lists sent notifications with their channel, type, and send time.
- Admin panel now logs outgoing emails in a dedicated mail log.

### Changed

- Dropbox connection callback now redirects back to the connect page, logs the token exchange and clears cached access
  tokens.
- README now lists the new `video:cleanup` command.

## [2.0.2] - 2025-08-20

### Fixed

- fixed typo in inbox-option in ingest:unzip.

## [2.0.1] - 2025-08-20

### Fixed

- fixed typo in inbox-option in ingest:unzip.

## [2.0.0] - 2025-08-20

### Added

- Settings are now grouped into categories and cached for faster access.
- New "Batches" page in the admin area shows videos, channels and offers together.
- All expiration times for links now follow one consistent rule.
- New `ingest:unzip` command extracts pending archives automatically.
- Scheduler entry for `ingest:unzip` runs the extraction every ten minutes.
- Shared locking via `LockJobTrait` prevents parallel runs of ingest commands.
- Admin panel includes a log viewer for inspecting application logs.
- Admin panel includes a Dropbox connect page for linking accounts.
- **Dropbox connect page now shows the access token expiration date when the account is connected.**
- Deployment guide added to the documentation.

### Changed

- The way the app reads settings has changed. If you have custom tools that fetch settings, they may need updates.
- Standardized batch handling by introducing `BatchTypeEnum` and migrating usages from the previously mixed enum.
- `ingest:scan` now supports lock options and a configurable target disk.
- Cron failures send notifications to the admin email setting instead of a fixed address.

### Removed

- Removed the old type enum and related aliases. **Breaking change:** public APIs now accept `BatchTypeEnum`.

### Fixed

- **ClipsRelationManager** now correctly displays values in the **Video** resource.
- The app now checks if a Dropbox link is still valid and asks you to reconnect when it has expired.

## [1.2.1] - 2025-08-18

### Fixed

- Fixed an issue where `expire_at` was not set if a link was never visited, causing video-to-channel assignments to
  never expire. `expire_at` is now reliably set and expirations are enforced regardless of link access.

## [1.2.0] - 2025-08-14

### Added

- Settings can now be changed directly in the browser, with clear labels and safe defaults.
- Each setting understands its type (text, number, yes/no, list), making wrong entries less likely.

### Changed

- Download links opened from the admin area no longer count toward viewer statistics.
- The lifetime of download links can be adjusted in the new settings screen.
- Importing clip information from CSV files is more forgiving and gives clearer warnings.
- Dropbox connections treat empty tokens as missing, reducing sync errors.

### Fixed

- General reliability improvements and more automated tests.

## [1.1.3] - 2025-08-14

### Added

- MIT license clarifies how the software can be used.
- Many more automated tests to catch problems early.

### Changed

- The video dashboard now has a simpler date filter.
- Video code tidied up for smoother performance.
- Tests skip the weekly maintenance task so checks run faster.

### Removed

- Old channel notification emails that were no longer used.

## [1.1.2] - 2025-08-13

### Changed

- Added many new automated tests so issues are caught before they affect you.
- Removed outdated code to keep the app running smoothly.
- Updated project documentation for clearer setup instructions.

### Fixed

- Small fixes across the app for better stability.

## [1.1.1] - 2025-08-12

### Changed

- Made session cookie name environment-aware. In config/session.php the default 'cookie' now includes the APP_ENV
  suffix (e.g., myapp_session_staging). You can still override via SESSION_COOKIE.

### Fixed

- Resolved intermittent 419 Page Expired errors on staging (Filament/Livewire) caused by cross-environment cookie name
  collisions. Set SESSION_COOKIE=staging_session and cleared config cache.

## [1.1.0] - 2025-08-11

### Added

- Comprehensive setup guides and workflow documentation, including examples for queue worker and production Reverb
  server configuration.
- GitHub links in the web and email footers for easy project access.
- Legal pages for Imprint and Privacy Policy, linked directly in the footer.
- Real‑time ZIP download modal with per‑file progress and WebSocket updates.
- Filament-based administration interface for managing channels, assignments, and static pages.

### Changed

- ZIP downloads now automatically mark assignments as downloaded.
- Improved download modal layout for clearer progress tracking.

## [1.0.1] - 2025-08-10

### Changed

- "Download selected" button disabled temporarily due to a bug.

## [1.0.0] - 2025-08-09

### Added

- First stable release of the platform.
- User accounts with secure authentication.
- Create personal channels to organize videos.
- Upload, stream, and download videos.
- Built-in video player with playback controls.
