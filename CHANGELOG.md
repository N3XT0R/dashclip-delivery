# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

