# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [4.0.0] - not released yet

### Added

- **Ingest Pipeline Architecture**
    - Introduced a modular ingest pipeline to process videos through atomic workflow steps.
    - Added `IngestPipeline` to orchestrate the ingest workflow.
    - Added `IngestStepInterface` to standardize pipeline steps.
    - Introduced `IngestContext` to carry runtime state across pipeline steps.
- **Ingest Workflow Steps**
    - Added step-based processing for ingest operations:
        - `LookupAndUpdateVideoHashStep`
        - `GeneratePreviewForVideoClipsStep`
        - `UploadVideoToDropboxStep`
- **Ingest Step Enumeration**
    - Added `IngestStepEnum` to centralize step identifiers.
- **Ingest State Management**
    - Added `IngestStateService` to manage ingest workflow step state via `video.meta`.
- **Job-based Ingest Execution**
    - Added `ProcessVideoIngestJob` to execute the ingest pipeline asynchronously.
    - Implemented `ShouldBeUnique` to prevent concurrent ingest runs for the same video.
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
- **Storage Integrity Cleanup**
    - added maintenance command to remove video records whose files no longer exist in storage

### Changed

- **Video-Upload Structure**
    - Moved VideoUpload to CreateRecord and refactored the upload flow to be more modular and testable.
- **Internationalization (i18n) Foundation**
    - migrated hardcoded German UI strings to the i18n system.
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

### Removed

- **Notification Table Resource**
    - Removed the deprecated `NotificationTableResource` and `Notification` model.
    - Users should use the new user notification center available in the `/standard` panel for managing notifications.
    - The new system logs notification mails by default and provides an improved user experience.
- **BatchResource**
    - ChannelRelationManager removed
- **console.php**
    - removed obsolete scheduler cron entries
    - removed the corresponding command classes and related tests

### Security

- **Composer Packages**
    - upgraded packages to newest version (e.g. laravel)

