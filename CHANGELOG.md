# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [4.2.0] - 2026-07-25

### Security
- **Packages**
    - upgraded packages to newest version (e.g. laravel or npm dependencies)

## [4.1.0] - 2026-07-15

### Added

- **My Offers navigation**
    - added a navigation badge showing the current channel's total number of available video offers,
      improving visibility and orientation for less experienced users in line with ISO 9241
      usability principles.

### Fixed

- **Assignment distribution**
    - returned expired videos without a download to subsequent distribution pools and stopped
      expired queued offers from being displayed as fully distributed.
    - marked both queued and notified assignments as expired after their TTL so undownloaded videos
      can be triggered again by subsequent distribution runs.
    - stopped requeueing a video as soon as any of its assignments has been picked up.
- **Standard panel video search**
    - fixed text searches failing when Filament searched computed bundle and role fields as video
      table columns instead of querying the related clips.

## [4.0.1] - 2026-07-15

### Fixed

- **Authorization / Shield seeding**
    - regenerated the Shield seeder with the current role and permission assignments, corrected
      direct-permission lookup by permission name, and added support for restoring optional tenant,
      user, and user-tenant data.

### Security
- **Packages**
    - upgraded packages to newest version (e.g. laravel or npm dependencies)

## [4.0.0] - 2026-07-07

### Changed

- Marked v4 as the first stable release

## [4.0.0-beta.9] - 2026-07-07

### Fixed

- **UX**
    - replaced the experimental video upload page title, subheading, and bundle ID helper text with clearer upload guidance.

## [4.0.0-beta.8] - 2026-07-06

### Fixed

- **Assignments**
    - persisted offer notification expiry timestamps on assignments so newly distributed videos can
      expire and requeue correctly after notification.

## [4.0.0-beta.7] - 2026-07-05

### Fixed

- **UX**
    - added exact date-time tooltips to relative date columns in admin Filament resources.
    - moved hardcoded Filament resource and page labels into English and German language files.
    - fixed the channel application benefits panel contrast in dark mode.

## [4.0.0-beta.6] - 2026-07-03

### Fixed

- **Assignments**
    - respected channel weekly quotas across assignment batches so channels that already reached
      their weekly limit no longer keep receiving new offers in later distribution runs.

## [4.0.0-beta.5] - 2026-07-03

### Fixed

- **Assignments**
    - fixed assignment expiration calculation when cached configuration values for the default TTL are stored as strings.

### Security

- **Composer Packages**
    - upgraded packages to newest version (e.g. laravel)

## [4.0.0-beta.4] - 2026-06-06

### Fixed

- **AssignmentService**: 
  - Fixed a null pointer exception in `canReturnAssignment()` when `expires_at` is `null` 
    assignments without an expiry date are now correctly treated as non-expired.

### Security
- **Packages**
    - upgraded packages to newest version (e.g. laravel or npm dependencies)


## [4.0.0-beta.3] - 2026-05-30

### Fixed

- **Horizon video ingest processing**
    - Fixed an issue where `ProcessVideoIngestJob` could fail to be dispatched
      when stale unique job locks remained after interrupted processing or
      worker timeouts.
      Replaced `ShouldBeUnique` with
      `ShouldBeUniqueUntilProcessing` to prevent persistent locks from blocking
      future ingest jobs.
      Increased the job timeout to better support long-running video ingest
      processing.
      Unique job identifiers are now environment-aware to avoid collisions
      between staging and production environments sharing the same Redis
      instance.

## [4.0.0-beta.2] - 2026-05-30

### Fixed

- **ChannelWorkspace cluster access**
    - Fixed missing permission validation in `ChannelWorkspace::canAccess()`: the
      Channel-Workspace cluster did not properly enforce access restrictions and
      could appear in navigation without the intended authorization check.
      Visibility is now limited to users with the
      `page.channels.access` permission (Channel-Operators).

## [4.0.0-beta.1] - 2026-05-29

### Added

- **WebDAV ingest pipeline**
    - `ZipUploadedListener` handles `FileCreatedEvent` and `FileUpdatedEvent` from the WebDAV
      package and dispatches `ProcessWebDavZipJob` for every `.zip` upload; registered in
      `AppServiceProvider`
    - `ProcessWebDavZipJob` extracts the archive via `UnzipService::extractSingle()`, creates
      `Video` records on the `import` disk, fires `VideoQueuedForIngest` for each new file —
      the single entry point into the existing ingest pipeline — and imports any accompanying
      CSV metadata file (same `info.csv` column format) via `CsvService::importCsvForDisk()`
    - `UnzipService` extended with `extractSingle(string $absoluteZipPath, string $absoluteTargetDir): bool`
      for single-archive extraction with automatic deletion on success; uses `IO\FileNotFoundException`
      on missing archive (ADR 0004)
    - `ingest:webdav` Artisan command scans all WebDAV user directories for ZIP archives and queues
      them via `ProcessWebDavZipJob`; acts as periodic fallback; scheduled every 15 minutes
    - `clean:webdav-non-zip` Artisan command removes non-ZIP files from WebDAV user directories;
      scheduled hourly

- **WebDAV policy**
    - introduced `WebDavPathPolicy` that wraps the package's `PathPolicy` via composition (the
      original class is `final`) and restricts `createFile` and `write` to `.zip` files only;
      `read`, `delete`, and `createDirectory` remain unrestricted
    - registered the policy in `PolicyServiceProvider` to override the package default for
      `PathResourceDto`

### Fixed

- **Dropbox upload**
    - `DropboxUploadService` switched to exclusively session-based uploads
      (`uploadSessionStart` → `uploadSessionAppend` → `uploadSessionFinish`); `upload()` is now
      only called for empty files, since it internally uses `fstat()` and does not accept PSR-7
      streams
    - Chunks are now passed as `LimitStream` (PSR-7) instead of strings via `fread()`;
      Guzzle no longer buffers the request bodies through `php://temp` to `/tmp`

- **FFmpeg / storage**
    - redirected FFmpeg temporary files from `/tmp` to `storage/app/ffmpeg-tmp` via `laravel-ffmpeg`
      config (`temporary_files_root` and `temporary_files_encrypted_hls`); stale temp files from
      crashed Horizon workers no longer exhaust the system `/tmp` partition
    - added `clean:ffmpeg-tmp` Artisan command that removes files older than 2 hours from
      `storage/app/ffmpeg-tmp`; scheduled to run hourly via the Laravel scheduler

## [4.0.0-alpha.4] - 2026-04-29

### Added

- **adr**
    - added architecture decision records for class naming, SOLID and design-pattern usage, exception hierarchies,
      method-level PHPDoc conventions, changelog maintenance policy, and Conventional Commits
    - documented in `AGENTS.md` that project ADRs are binding guidance, feature implementation should be
      token-efficient, and Superpowers should only be used on explicit request
    - clarified in `AGENTS.md` that project ADRs are the only normative rules for covered topics, enterprise
      architecture guidance comes from ADRs, and Laravel is infrastructure
- **tooling**
    - added a Pint configuration using the PSR-12 preset while preserving camelCase PHPUnit method names

### Changed

- **Refactored ZIP job payload handling**
    - Introduced `AssignmentZipDto` to encapsulate ZIP creation parameters
    - Replaced primitive constructor arguments in `BuildZipJob` with DTO
    - Improved separation between domain data and infrastructure layer
    - Simplified method signatures and reduced parameter coupling

### Fixed

- **Dropbox / storage**
    - fixed `/tmp` exhaustion caused by Guzzle buffering Dropbox response bodies via `php://temp`;
      both the Storage disk driver and `DropboxUploadService` now pass `stream: true` to the Guzzle
      client so file data streams directly from the socket without touching `/tmp`
    - fixed a stream-leak in `ZipService` where a failed `stream_copy_to_stream` call (Laravel
      converts PHP warnings to `ErrorException`) left the Guzzle HTTP stream unclosed, causing
      `/tmp` files to accumulate across failed jobs until disk space was exhausted
    - `ZipService::build()` now cleans up `zips/tmp/` staging files in a `finally` block so they
      are always removed regardless of whether the job succeeds or fails

- **testing**
    - fixed the OnboardingWizard notification assertion to use Filament's notification testing API

## [4.0.0-alpha.3] - 2026-04-11

### Added

- **Laravel/horizon**
    - added Laravel Horizon for advanced queue monitoring and management
    - provides a dashboard to track job throughput, runtime, failures, and retry attempts
    - enables real-time insights into queue performance and bottlenecks
    - supports multiple queue connections and job types with detailed metrics
    - allows for easy identification of failed or long-running jobs for troubleshooting
- **Laravel/Boost**
    - added Laravel Boost integration
    - exposes application context (routes, models, config, logs, etc.) to AI tools
    - allows interaction with the application via MCP (Model Context Protocol)
    - enables code execution and inspection through Tinker
    - provides access to framework-specific documentation and conventions
    - improves AI-assisted development (debugging, refactoring, code navigation)

### Changed

- **Queue Worker Configuration**
    - updated queue worker configuration to use Horizon's `horizon` connection for better performance and monitoring
    - adjusted `ProcessVideoIngestJob` to be dispatched to the `horizon` queue connection
    - ensures that all ingest processing benefits from Horizon's features and monitoring capabilities
    - updated documentation to reflect the new queue configuration and Horizon setup instructions
- **Laravel 13 Upgrade**
    - upgraded Laravel framework to version 13 for improved performance, security, and new features
    - updated related dependencies to ensure compatibility with Laravel 13
    - refactored code to address any breaking changes introduced in Laravel 13
    - tested all major functionalities to ensure stability after the upgrade

### Fixed

- Minor bugfixes around the application and ingest pipeline based on testing and feedback during the alpha phase.

## [4.0.0-alpha.2] - 2026-04-07

### Added

- **Video Ingest Pipeline**
    - added `ValidateInputFileStep` as a preliminary validation step to ensure that the source file exists and is
      processable before executing further ingest steps
    - prevents downstream failures (e.g. hash calculation or preview generation) by stopping the pipeline early when the
      input file is missing or invalid
    - introduced `isInvalid` flag in `IngestContext` to distinguish invalid input from duplicate detection
    - separates technical validation failures from business-level duplicate handling, improving pipeline semantics and
      observability
    - enhanced pipeline control flow to stop execution when a video is marked as invalid or duplicate
    - ensures consistent early termination behavior across all ingest steps
    - added support for detecting missing ingest steps for already processed videos based on the current pipeline
      definition via `IngestStateService`
    - enables identification of videos that were processed with an outdated pipeline definition
    - added maintenance command to requeue completed videos with missing ingest steps
    - enables retroactive execution of newly added pipeline steps without reprocessing already completed steps

### Changed

- **Video Deletion Handling**
    - introduced soft deletes for videos to prevent immediate physical data loss
    - decoupled logical deletion from physical file removal to improve system consistency
    - videos are now hidden from users via soft delete while underlying files remain intact until explicitly removed
    - prepared the system for deferred cleanup strategies (e.g. scheduled force deletion and storage cleanup)
- **Video Ingest Status Query**
    - refactored ingest metadata extraction and interpretation logic into `IngestStateService`
    - centralized access to ingest step state (status, attempts, timestamps, current step) to avoid duplication across
      use cases
    - simplified `GetVideoIngestStatusUseCase` by delegating state evaluation to the service layer
    - improves consistency between backend logic and frontend status representation
- **Video Ingest Triggering**
    - introduced a dispatch delay for `ProcessVideoIngestJob` to decouple ingest processing from the initial request
      lifecycle
    - prevents blocking behavior during video creation (e.g. Filament form submission and redirect) when using
      synchronous or slow queue processing
    - improves user experience by ensuring immediate response after form submission while deferring heavy ingest
      processing to the background

## [4.0.0-alpha.1] - 2026-04-02

### Added

- **Video Ingest Pipeline** [#265](https://github.com/N3XT0R/dashclip-delivery/issues/265)
    - introduced a deterministic, step-based ingest pipeline that processes videos through isolated and reproducible
      workflow steps
    - added `IngestPipeline`, `IngestStepInterface`, and `IngestContext` to explicitly model the ingest workflow and its
      data flow
    - implemented idempotent step execution, allowing safe reprocessing of individual steps without affecting already
      completed ones
    - added dependency-aware step execution so that only incomplete or invalid steps and their dependent steps are
      re-run during retries
    - added `IngestStepEnum` to centralize and standardize ingest step identifiers
    - added `IngestStateService` to persist and track per-video workflow progress and step results via `video.meta`
    - enables full traceability of ingest progress, including detection of failed or stuck steps on a per-video basis
    - added `ProcessVideoIngestJob` for asynchronous and decoupled pipeline execution
    - implemented `ShouldBeUnique` to prevent concurrent ingest runs for the same video
    - added maintenance commands to requeue failed or stale ingest jobs
    - provides a robust recovery mechanism for interrupted workflows (e.g. worker crashes or restarts) by resuming only
      incomplete steps
    - includes step-based processing such as:
        - `LookupAndUpdateVideoHashStep`
        - `GeneratePreviewForVideoClipsStep`
        - `UploadVideoToDropboxStep`
- **Video Ingest Status Query**
    - added `GetVideoIngestStatusUseCase` to provide a unified and frontend-friendly representation of the ingest
      pipeline state per video
    - combines the statically defined pipeline steps (via Laravel container tagging) with the persisted ingest state
      stored under `video.meta.ingest`
    - ensures a complete and deterministic step list, even if not all steps have been executed yet
    - added `IngestStatusDto` as an aggregate data transfer object representing overall ingest progress
    - added `IngestStepStatusDto` to encapsulate per-step status information (status, attempts, current step flag)
    - calculates derived progress metrics such as total steps, completed steps, and percentage completion
    - intentionally excludes error details from the DTO to provide a clean and UI-focused data structure
    - integrated ingest status into the Filament video detail view via a custom `ViewEntry`
    - enables frontend features such as progress indicators and step-based status visualization without exposing
      internal error handling logic
- **Event-driven Ingest Trigger**
    - Added `VideoCreatedForIngest` event to trigger the ingest workflow.
    - Added a dedicated listener to dispatch `ProcessVideoIngestJob`.
- **Service Container Integration**
    - Added `IngestServiceProvider` to register ingest pipeline steps via Laravel container tagging.
- **Module System**
    - Added `coolsam/modules` to provide Filament integration for the package-like module structure managed by
      `nwidart/laravel-modules`.
    - Modules are generated using `php artisan module:make` and are bootstrapped via their own `ModuleServiceProvider`.
    - Added support for organizing Filament resources and related components within modules.
- **Video Storage Cleanup** [#225](https://github.com/N3XT0R/dashclip-delivery/issues/225)
    - added maintenance command to remove database records for videos whose files are missing from storage
    - resolves inconsistencies where videos exist in the database but no longer exist in storage
    - related issues/features:
        - [#257](https://github.com/N3XT0R/dashclip-delivery/issues/257)
        - [#225](https://github.com/N3XT0R/dashclip-delivery/issues/225)
        - [#224](https://github.com/N3XT0R/dashclip-delivery/issues/224)
- **Config**
    - introduced constant classes for config categories:
        - `DefaultConfigEntry`
        - `EmailConfigEntry`
        - `FFMPEGConfigEntry`

### Changed

- **Video Ingest Flow**
    - replaced the legacy monolithic ingest workflow with the new modular step-based ingest pipeline
- **Video-Upload Structure**
    - Moved VideoUpload to CreateRecord and refactored the upload flow to be more modular and testable.
- **Internationalization (i18n) Foundation**
    - migrated hardcoded German UI strings to the i18n system.
- **Channel Welcome Email**
    - migrated email content to the i18n system and replaced hardcoded strings with translation keys.
    - replaced the custom token implementation with the centralized `TokenApprovalController` and `ActionTokenService`.
- **Separation of Concerns**
    - Moved Business Logic to Services from Models and Controllers.
- **Ingest Workflow**
    - Replaced the legacy monolithic ingest implementation with a modular step-based pipeline architecture.
- **Clip Handling**
    - Updated ingest processing to support multiple clips per video.
    - Replaced single clip handling with a clips collection in `IngestContext`.
- **Preview Generation**
    - Updated preview generation to process previews for all clips belonging to a video.
- **Processing Status Handling**
    - Centralized ingest lifecycle state using the `processing_status` column with `ProcessingStatusEnum`.
- **Filament Panel Structure**
    - Moved `Filament/Resources`, `Filament/Pages`, `Filament/Widgets`, and `Filament/Clusters` to `Filament/Admin` to
      clearly separate the Admin panel from the default panel structure.
- **console.php**
    - replaced hardcoded scheduler command names with command class references
- **Config**
    - replaced hardcoded config category strings in `ConfigService` calls with constants from:
        - `DefaultConfigEntry`
        - `EmailConfigEntry`
        - `FFMPEGConfigEntry`
- **Preview**
    - updated video preview component to automatically refresh using polling while the preview is not yet available
    - stops polling once the preview has been generated or processing has completed/failed
    - improves user experience by displaying the preview as soon as it becomes available without requiring a manual page
      reload

### Removed

- **Video Ingest Flow**
    - replaced the legacy monolithic ingest workflow with the new modular step-based ingest pipeline
- **Notification Table Resource**
    - Removed the deprecated `NotificationTableResource` and `Notification` model.
    - Users should use the new user notification center available in the `/standard` panel for managing notifications.
    - The new system logs notification mails by default and provides an improved user experience.
- **BatchResource**
    - ChannelRelationManager removed
- **console.php**
    - removed obsolete scheduler cron entries
    - removed the corresponding command classes and related tests
- **Channel Welcome Email / Approval Flow**
    - removed `Channel::getApprovalToken()` from the model.
    - removed the dedicated `ChannelApprovalController` and its associated route.
    - cleaned up `ChannelService` by removing legacy approval token handling.

### Security

- **Composer Packages**
    - upgraded packages to newest version (e.g. laravel)
