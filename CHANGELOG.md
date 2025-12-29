# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Channel Management Interface for Channel Operators**  
  Introduced a new Filament-based management interface that allows channel operators to independently manage their own
  channel configuration and access control within the platform.  
  The interface provides a dedicated workspace for channel-level administration:
    - Channel operators can edit general channel settings (e.g. name, visibility, and operational status) via a
      permission-restricted edit view tied directly to the active channel context.
    - A user management section enables channel operators and administrators to review all users with access to the
      channel, including verification status and role-based permissions.
    - Authorized users can revoke channel access directly from the interface, ensuring immediate enforcement of access
      changes without requiring global administrative intervention.
    - All actions are integrated with the existing permission and audit infrastructure, enabling traceable and
      compliant management of channel ownership and user access.  
      This addition strengthens channel-level autonomy, improves operational security, and provides a clear,
      self-service management model for channel operators while maintaining full administrative oversight.
- **Pending Channel Access Request Badge in Admin Interface**  
  Introduced a visual badge indicator in the admin area that displays the current number of pending channel access
  requests.  
  The badge is integrated directly into the channel access request navigation entry and updates dynamically based on
  request status:
    - Administrators can immediately see how many channel access applications are awaiting review without navigating
      into the request list.
    - The badge reflects only requests in a pending state, ensuring a clear and accurate signal for required action.
    - This improvement reduces response time for access approvals and helps prevent overlooked requests during daily
      administrative workflows.  
      The addition enhances operational awareness for administrators and improves the overall efficiency of channel
      access management.

### Fixed

- **Incorrect User Removal During Channel Access Revocation in Admin Panel**  
  Fixed an issue in the admin panel where revoking channel access from the user management interface always removed the
  currently authenticated user instead of the selected channel user (owner record).  
  The revocation logic has been corrected to consistently operate on the relation manager’s owner record, ensuring that
  access removal actions now target the intended user:
    - Channel access is now revoked for the selected user in the table, not implicitly for the active administrator.
    - The fix prevents accidental self-removal and ensures predictable, role-safe behavior during administrative
      access management.
    - The corrected behavior aligns the revoke process with the displayed UI context and audit expectations.  
      This fix improves reliability and safety of channel access management within the admin panel.

## [3.2.0] - 2025-12-28

### Added

- **Channel Access Application Workflow for Channel Operators**  
  Introduced a new Filament page that enables channel operators to request access to their video pool via a guided
  application form.  
  The process supports both existing and new channels:
    - Users can select their channel from a searchable list or submit detailed information for a new channel that is not
      yet present in the system.
    - Applications for new channels collect all necessary data for direct approval and automated channel creation in the
      admin backend.
    - A mandatory "Terms of Service" acceptance checkbox enforces explicit consent to platform usage conditions at the
      time of channel access request, independent of generic registration.
    - The workflow is fully permissioned and compatible with audit logging, enabling precise compliance tracking of
      access requests and legal acceptance.  
      This addition improves security for channel assets, clarifies account/channel responsibility, and streamlines the
      onboarding of new partners.
- **Channel Owner Dashboard**
    - Added a dedicated dashboard for channel owners to manage and review offered video clips.
    - Provides a tab-based overview of available, downloaded, expired, and returned offers.
    - Enables bulk download of selected offers via background ZIP generation with progress tracking.
    - Supports bulk downloads with asynchronous ZIP creation and real-time progress updates.
- **Internationalization (i18n) Foundation**
    - Introduced English base language files.
    - Started migrating hardcoded German UI strings to the i18n system.
- **Application Use-Case Layer**
  Introduced a dedicated application/use-case layer to orchestrate complex
  channel workflows such as approvals, assignments, and event dispatching.
  This layer provides a single entry point for business workflows and
  prepares the codebase for reuse across multiple entry points (e.g. UI pages).
- **Channel Access Approval Notifications & Workflow Completion**
    - Completed the end-to-end channel access approval lifecycle, covering application submission, approval decision,
      and
      post-approval communication.
    - Introduced a secure, purpose-bound action token mechanism to validate and process approval actions via one-time
      links.
    - Added email notifications to channel owners or authorized team members when a channel access request is submitted.
    - Approval links are secured using single-use, time-limited tokens and trigger the approval workflow via a dedicated
      confirmation endpoint.
    - Introduced user notifications to inform applicants when their channel access request has been approved.
    - Notifications are delivered via email and in-app (Filament) notifications and are informational only (no further
      action required).
    - Implemented an event-driven approval flow using domain events and listeners to decouple token consumption,
      access assignment, and notification dispatching.
    - Ensures consistent behavior across all approval paths and supports future extensibility of approval-based
      workflows.
- **Offer Comments for Channel Operators**  
  Enabled channel operators to add an internal comment to an offer.  
  This allows channel owners to attach contextual or editorial notes to a submitted video, for example:
    - planned usage context (e.g. “Video will be featured in episode XY”)
    - internal editorial or organizational remarks  
      The comment can be edited via the offer detail view and is stored together with the offer record, ensuring that
      the
      information remains available throughout the entire offer lifecycle (available, downloaded, returned).  
      This feature improves editorial planning, internal communication, and contextual clarity without affecting the
      original video submission or its metadata.
- **SEO & OpenGraph Metadata Foundation**
  Introduced a dedicated SEO metadata structure to improve search engine indexing
  and link preview quality across public-facing pages.
    - Added a page-level SEO slot to allow explicit control over meta titles,
      descriptions, canonical URLs, and OpenGraph metadata.
    - Optimized the landing page title and heading structure to clearly reflect
      the platform’s primary use case and target audience.
    - Introduced OpenGraph metadata for improved link previews in messaging and
      collaboration tools (e.g. WhatsApp, Slack, Discord).
    - Ensured SEO metadata is defined per page without coupling content logic
      to layout templates, preserving clean separation of concerns.
    - Prepared the frontend architecture for future extensions such as
      structured data (Schema.org) and multilingual SEO.

      This addition improves discoverability, link sharing quality, and
      long-term maintainability of SEO-related concerns without introducing
      marketing-driven coupling into the codebase.

### Changed

- **Approval Token Hashing Algorithm**  
  Updated the hashing algorithm for channel approval tokens from `sha1` to `sha256` to enhance security and align with
  modern cryptographic standards.  
  The stronger hashing mechanism reduces the risk of collision attacks and improves overall integrity of the approval
  workflow.
- **Download Workflow Refactoring**  
  Refactored client-side download handling to decouple it from backend route and domain changes.  
  Updated the JavaScript download logic to rely on stable job-based identifiers instead of batch-specific parameters.  
  Improved the robustness of the ZIP download flow by isolating frontend logic from backend routing details.
- **Optional Portal-Based Access for Channel Operators**  
  Channel operators can continue to access offers via existing links.
  In addition, an optional portal-based workflow is now available, allowing registered
  users to manage offers, downloads, and status information in a central location.
  This provides a foundation for a more streamlined experience as the platform evolves.
- **ChannelCreated Event**
    - Moved the `ChannelCreated` event dispatch from the `CreateChannel`-Page to
      `ChannelObserver::created` to ensure consistent triggering across all
      creation pathways.
- **UI Text Handling**
    - Migrated static German UI texts to the i18n system.
    - Improved maintainability and consistency of user-facing strings.
- **Offer Notification Mail Handling**  
  Adjusted the notification logic so that emails about new offers are no longer sent when no offers are available.  
  This prevents misleading or empty notifications and aligns outbound communication with actual offer availability.

### Fixed

- **Incorrect Membership Check Affecting Channel Access Application State**  
  Fixed an issue where the `channel_id` field in the Filament v4 channel access application form was always submitted as
  `null`, even when a valid channel was selected.  
  The underlying cause was an invalid relation check using `has($model)` on a BelongsToMany relation, which resulted in
  a framework-level exception and prevented the form state from hydrating correctly.  
  The method was updated to use a proper pivot-aware membership check via  
  `wherePivot('user_id', $user->getKey())`, ensuring reliable evaluation of team membership without interrupting the
  request lifecycle.  
  As a result, all form fields — including dynamically displayed Select components — now hydrate and persist correctly,
  ensuring complete and accurate application data for review and approval.
- **Catch-All Mail Address Handling in Non-Production Environments**
    - Fixed an issue where catch-all email addresses were appended instead of replacing original recipients in
      local, testing, and staging environments.
    - Centralized mail recipient resolution to ensure that all outgoing emails are safely redirected to the configured
      `mail.catch_all` address outside of production.
    - Prevented unintended delivery of test and development emails to real user inboxes while preserving production
      behavior.
- **Assignment Requeue Logic**
    - Fixed an edge case where expired assignments with existing downloads were incorrectly requeued by excluding
      assignments in `expired` state when valid downloads are already present.

### Changed

- **Refactored Channel Access Checks into Reusable Traits**  
  Moved channel access verification logic out of the `MyOffers` page into dedicated, reusable traits to ensure
  consistent
  and maintainable access control across the application.  
  The refactor centralizes channel permission checks and decouples access logic from individual pages:
    - Channel access validation is now implemented once and shared across all channel-related pages and components.
    - This change eliminates duplicated access checks and reduces the risk of inconsistent authorization behavior.
    - The new trait-based approach improves code reuse, testability, and long-term maintainability of channel access
      logic.  
      As a result, channel access enforcement is now clearer, more robust, and easier to extend as additional
      channel-scoped features are introduced.

### Deprecated

- **Notification-table Resource**  
  The `NotificationTableResource` and `Notification`-Model has been deprecated and will be removed in a future
  release.  
  Users are encouraged to transition to the new user notification center available in the `/standard` panel, which
  provides a more robust and user-friendly interface for managing notifications. Mails are logged by default in the new
  system.
- **Zip Download Route**  
  The `/zips/{batch}/{channel}` route has been deprecated in favor of the new
  `/zips/channel/{channel}` route.
- **Channel-Approval**
    - the `/channels/{channel}/approve/{token}` route has been deprecated in favor of
      `/action-tokens/approve/{purpose}/{token}`.

### Security

- **Composer Packages**
    - upgraded packages to newest version (e.g. laravel)

## [3.1.7] - 2025-12-28

### Fixed

- **Video-Team Assignment**
    - Videos without an assigned team are now automatically processed via a scheduled command, ensuring proper team
      assignment and preventing delivery and permission issues.

## [3.1.6] - 2025-12-26

### Fixed

- **User-Registration**
    - Filament's registration flow now correctly uses the panel's active guard (`standard`) when attaching the default
      user role.

## [3.1.5] - 2025-12-22

### Fixed

- **Storage**
    - Fixed an issue where the storage disk could be overloaded with temporary files during video uploads.
    - Temporary files are now cleaned up daily via a scheduled command to prevent disk space exhaustion.

## [3.1.4] - 2025-12-22

### Fixed

- **Team channel assignment**
    - Fixed an issue where team channels were not correctly assigned when a team was found.
    - Global channel and quota logic now only applies when no team channels are available.

## [3.1.3] - 2025-12-16

### Fixed

- **Footer Positioning in Filament Panels**
    - Resolved an issue where the footer overlapped with content in Filament panels when the page content was long.
    - Improved overall layout consistency across different screen sizes and content lengths.
- **Web Video Upload**
    - Fixed an issue where uploaded videos were initially associated with an implicit dynamic storage context
      instead of their actual source disk.
    - Ensured that the original storage disk is preserved during ingestion and correctly transitioned to the
      target disk on successful upload, preventing inconsistent file states and access errors.

### Removed

- Removed unused, undocumented code paths that were never part of the public API.

## [3.1.2] - 2025-12-12

### Fixed

- **Filament v4.3.1 Upgrade**
    - Upgraded Filament to version 4.3.0 for security patches.

## [3.1.1] - 2025-12-12

### Fixed

- **Filament v4.3.0 Upgrade**
    - Upgraded Filament to version 4.3.0 for security patches.

## [3.1.0] - 2025-12-05

### Added

- **GDPR-Compliant Cookie Notice**
    - Introduced a minimal, unobtrusive cookie banner informing users that the site only uses strictly necessary
      cookies.
    - The banner is displayed only when no `cookie_consent=true` flag is present.
    - User consent is persisted for one year via a client-side cookie.
    - Implemented without external dependencies and fully independent of Filament’s theme or styling system.

### Changed

- **Command Descriptions and Signatures Localized to English**
    - Updated multiple Artisan commands to use clear, consistent English descriptions and option texts.
    - Improved readability and standardization across commands such as:
        - `assign:uploader`
        - `info:import`
        - `channels:send-welcome`
        - `video:cleanup`
        - Dropbox token refresh command
        - Offer link dispatch command
    - Ensures that command usage, help output, and documentation are now fully aligned with the project's
      English-language conventions.

### Fixed

- **Notification Settings Not Persisting in Profile Edit**
    - Fixed an issue where dynamically generated notification checkboxes  
      (`notifications.mail.types.{NotificationClass}`) were not being saved in Filament v4.
    - Form state is now correctly read from the nested structure `notifications.mail.types` instead of non-existent flat
      keys.
    - `handleRecordUpdate()` now persists each notification preference through the `UserMailConfigRepository`.
    - Removed the notification subtree from form data before calling the parent update handler to prevent unintended
      writes to the `users` table.
- **Incorrect Role Assignment Guard During User Registration**
    - Resolved an issue where newly registered users were assigned a role under the wrong authentication guard.
    - Filament’s registration flow now correctly uses the panel’s active guard (`standard`) when attaching the default
      user role.
    - Prevented silent failures where Spatie Permission rejected role assignment due to a guard mismatch.
    - Ensures that newly registered users can authenticate and access the panel without encountering unexpected `403`
      errors.

## [3.1.0-beta.2] - 2025-12-05

### Added

- **Unique Video Processing Jobs**
    - Introduced `ShouldBeUnique` for the `ProcessUploadedVideo` job to prevent duplicate ingestion when the same file
      is
      uploaded or dispatched multiple times.
    - Each job is now uniquely identified via a `user_id:file_hash` composite key, ensuring that deduplication occurs
      per-user while still avoiding duplicate processing of identical uploads.
    - Activity logs now include additional metadata such as the job's retry attempt (`$this->attempts()`), improving
      traceability and debugging capabilities.
    - Upload events now record structured properties (e.g., `file`, `attempt`) consistently across all ingestion logs.

### Changed

- **Offer Expiration Logic**
    - Updated assignment expiration handling so that `expires_at` is now normalized to the end of the day (
      `endOfDay()`).  
      This ensures that a configured TTL (e.g., “7 days”) corresponds to a full calendar-day period as users naturally
      expect.
    - Previously, expirations were based on the exact creation timestamp, which was technically correct but resulted in
      shorter perceived validity windows.  
      The new logic guarantees that offers remain active consistently until the end of the final valid day.

## [3.1.0-beta.1] - 2025-12-04

### Added

- **assign:uploader Command**  
  Added a new CLI command that automatically assigns `user_id` to clips based on the `submitted_by` field.  
  This prepares the system for uploader-based distribution pools and improves data consistency.
- **Configurable Mail Catch-All for Local/Testing Environments**  
  Introduced an optional mail "catch-all" mechanism for non-production environments (`local`, `testing`, `staging`).  
  When `MAIL_CATCH_ALL` is defined in the environment configuration, all outgoing mails are automatically  
  redirected to this address and the subject is prefixed with the current application environment.  
  This prevents unintended delivery to real recipients during development or automated testing while keeping  
  the core codebase environment-agnostic and fully open-source compatible.
- **Standard-Filament-Panel (`/standard`)**  
  Introduced a second Filament Panel dedicated to end users.  
  The new panel provides a clean, reduced self-service interface for managing  
  user-owned data, integrations, and future tenant-level settings.  
  This establishes the foundation for fully isolated tenant environments.
- **Path-Based Tenant Routing (`/standard/*`)**  
  Implemented tenant scoping based on a static path prefix (`/standard`) instead of  
  subdomain- or domain-based tenancy.  
  This ensures predictable URLs, simplifies local development, avoids DNS  
  configuration requirements, and provides a stable foundation for user-facing  
  tenant resources and future integrations.
- **Team-Based Multi-Tenancy Foundation**  
  Introduced foundational multi-tenancy support via a user–team many-to-many relationship.
  This establishes the structural basis for tenant-scoped resources and prepares the system
  for future collaboration features, role-based access control, and paid tiers.
  Note: Teams are currently scaffolded but not yet query-scoped or permission-enforced.
  Full multi-tenancy isolation and access control will be implemented later after
  the role/permission architecture across multiple Filament panels is finalized.
- **Automatic Team Initialization (TeamSeeder)**  
  Added a seeder that creates a default personal team for all users who are not  
  assigned to any team.  
  Ensures every user has a valid tenant context for the `/standard` panel.
- **TeamRepository & UserRepository Enhancements**  
  Added new repository helpers (`getAllUsersWithoutTeam()`,  
  `createOwnTeamForUser()`, etc.) to manage team membership and  
  bootstrap team structures in a domain-driven, testable manner.
- **PanelUserPanelProvider**  
  Introduced a dedicated provider for the new `/standard` panel, including branding,  
  custom middleware, user authentication, and tenant bootstrapping logic.  
  Separates internal admin workflows from end-user functionality.
- **Video Detail View & Assignment Tracking**  
  Introduced a comprehensive detail page for end-user video management in the `/standard` panel.  
  Users can now view individual video metadata (title, duration, upload date, status),  
  preview thumbnails, and a complete assignment history for each video.  
  A new **AssignmentRelationManager** displays all channels receiving the video, including:
    - Channel name and description
    - Assignment status (notified, picked_up, expired, returned)
    - Offer expiration date
    - Download status with precise timestamps (e.g., "Downloaded on 03.11.2025 09:07")  
      This enables users to track their video distribution across channels in real-time  
      and provides full transparency into which channels have accepted or declined their content.
- **End-User Channel Management (`/standard` Panel)**  
  Introduced a new self-service interface that allows teams to manage  
  which channels they want to distribute their videos to.  
  Users can now select from all active channels and maintain their own  
  personal distribution pool.  
  The feature includes:
    - A dedicated *Select Channels* page
    - A table showing currently assigned channels
    - Attach/Detach actions for adding or removing channels
    - Automatic pivot synchronization (`channel_team`)  
      This completes the foundation for user-driven distribution pipelines in the  
      upcoming distribution engine.
- **User Notification Center (Filament Bell Icon)**  
  Enabled Filament’s notification system in the user-facing `/standard` panel, including the in-panel notification
  bell.  
  Users now receive real-time and database-backed notifications directly within the UI, providing clear visibility into
  all upload-related events.
- **Upload Processing Notifications (via `ProcessUploadedVideo` Job)**  
  Added two new user-facing notifications that are automatically dispatched by the `ProcessUploadedVideo` job:
    - **UserUploadProceedNotification** – sent when an uploaded video has been successfully processed.
    - **UserUploadDuplicatedNotification** – sent when an uploaded video is detected as a duplicate.  
      Each notification includes both a custom HTML email and a Filament in-app notification, ensuring consistent
      communication across channels.
- **Notification Preferences**
    - Introduced user-configurable email notification settings in the Filament *Edit Profile* page.
    - Added automatic discovery of user-facing notifications via a configurable `NotificationDiscoveryService`.
    - Implemented opt-in/opt-out handling per notification type using `UserMailConfigRepository`.
    - Added dynamic form generation in the profile section, including label translation and per-user default states.
    - Implemented optional caching layer via Decorator Pattern for notification discovery.
      The `CachedNotificationDiscoveryService` wraps the base service with configurable
      TTL and can be toggled per-request via DI parameters (`useCache`, `ttl`).
      Ensures discovery performance scales without impacting code simplicity.

### Changed

- **Repository Refactoring**  
  Extracted several Eloquent query segments from service classes into their corresponding repositories  
  (`VideoRepository`, `ClipRepository`, etc.), improving separation of concerns and maintainability.
- **assign:distribute Refactoring**  
  The assignment workflow has been partially refactored to reduce method complexity,  
  delegate responsibilities to dedicated services (e.g., `AssignmentService`, `BatchService`),  
  and prepare the distributor for upcoming uploader-based distribution logic (#121).  
  This improves testability, structure, and future extensibility of the assignment pipeline.
- **VideoRepository: Team- and Uploader-Based Partitioning**  
  Introduced the new `partitionByTeamOrUploader()` method, which groups videos  
  primarily by their associated team's slug and falls back to the uploader of  
  the first clip when no team assignment exists.  
  This establishes a flexible and tenant-aware distribution structure, enabling  
  the upcoming quota, routing, and assignment logic to operate on a team level  
  while still correctly isolating uploader-specific content in non-team scenarios.  
  Includes full integration test coverage across both team-bound and uploader-only  
  video scenarios.
- **Tenancy Activation & Role Resource Adjustments**  
  Updated Filament’s RoleResource configuration to disable tenant ownership for  
  global system-level roles.  
  This prevents Filament from enforcing tenant constraints on resources that  
  must remain globally scoped across all teams.
- **User–Team Relationship Standardization**  
  Consolidated the user–team architecture into a consistent many-to-many model  
  (`users ↔ teams` via pivot).  
  Prepares the system for features like multi-team membership,  
  team switching, invitations, and per-team access levels.
- **Filament Panel Structure Updated for Multi-Tenancy**  
  Enhanced panel configuration to correctly bootstrap tenant-aware routing,  
  resource discovery, and UI behavior across both `/admin` and `/standard` panels.  
  Ensures proper isolation of end-user data and clean separation of concerns.
- **Team-Based Channel Quota Overrides**  
  Added support for channel-specific quota overrides defined at the team level  
  via the `team_channel` pivot table.  
  When videos originate from a team, per-channel pivot quotas now take precedence  
  over the global `weekly_quota`, enabling contract- or partner-specific delivery  
  limits.  
  Uploader-only pools (non-team videos) continue to use the global quota  
  unchanged, ensuring full backward compatibility with existing behavior and  
  preserving all current test expectations.
- **VideoUpload UX Improvement**  
  Added a client-side JavaScript event in the video upload form that re-enables the “Upload” button only
  after the file upload has fully completed.  
  This prevents premature submissions, improves user experience, and increases reliability under slower network
  conditions.

### Fixed

- **Ingest**
    - Fixed long-running database transactions during video ingest that caused
      `Lock wait timeout exceeded` errors.
    - Moved preview generation and file upload outside of transactional scope to
      prevent table locks during heavy I/O operations.
    - Limited DB transactions to initial video creation and CSV import for minimal
      lock duration.
    - Removed redundant `DB::rollBack()` calls in non-transactional error paths.
    - `finalizeUpload()` now performs a single atomic `UPDATE` without wrapping it
      in a transaction.

## [3.0.1] - 2025-11-29

### Fixed

- **Logging**
    - Updated logfile permissions to `775` to allow shared read/write access (e.g. `root` and `www-data`).

- **Upload**
    - Increased maximum upload size to **2 GB**.
      Fixed – Ingest Deadlocks & Long-Running Transaction Issues

## [3.0.0] - 2025-11-15

### Added

- **Modular Ingest Architecture**
    - Fully redesigned, transaction-safe ingestion pipeline for both CLI and web.
    - Unified file access through the new `DynamicStorageService` (local, S3, Dropbox) with stream-based hashing.
    - New domain-specific exceptions (`InvalidTimeRangeException`, `PreviewGenerationException`) for predictable flow
      control.
    - Strongly typed result objects (`IngestStats`, `ClipImportResult`) replacing mutable arrays.
    - Extensive feature and integration test coverage using real video fixtures.
    - Dedicated `CsvService` with typed aggregation and improved error handling.

- **Adaptive Preview Engine**
    - Automatic compression with dynamic CRF values (30–36) depending on input file size.
    - Intelligent half-scaling for large inputs (>300 MB) to minimize preview output size.
    - Unified FFmpeg parameter handling with safe escaping and normalized CLI argument generation.
    - Fully configurable through database-driven FFmpeg settings and integrated with `pbmedia/laravel-ffmpeg`.

- **Channel Approval & Welcome Flow**
    - GDPR-compliant approval workflow using hashed confirmation tokens.
    - Automatic welcome emails for newly created channels and users.
    - Secure password delivery for admin-created accounts (never stored).
    - New `channels:send-welcome` command with dry-run and force modes.

- **Inbound Mail Processing & Logging**
    - New inbound mail handler with duplicate detection and structured metadata storage.
    - Central mail repository for inbound/outbound messages.
    - Integration tests for listeners, handlers, and mail log creation.

- **User Authentication & MFA**
    - Multi-factor authentication (TOTP and email codes).
    - Email verification and change verification.
    - Automatic role assignment for newly created users.
    - Full Filament Shield integration for role/permission management.

- **ZIP Download System**
    - Unified ZIP-based downloader for single and multi-file downloads.
    - Real-time progress events via WebSockets.
    - Consistent file naming, error handling, and logging.

- **Offer Link Analytics**
    - New `offer_link_clicks` table capturing clicks and user agents.
    - Dedicated Filament resource for statistics and analysis.

- **Version Service**
    - New `LocalVersionService` reading version information from Composer metadata.

### Changed

- **Preview Generation**
    - Updated FFmpeg defaults (CRF 33, unified scaling filter, consistent preset handling).
    - More efficient preview generation under high-load environments.

- **UI & Interaction**
    - Improved layout spacing, focus states, accessibility, and visual consistency.
    - Updated Filament v4 components and resources.

- **Uploads**
    - Increased maximum upload size to **1 GB** and extended upload timeout to ~25 minutes.
    - Improved handling of large uploads with Livewire configuration refinements.

- **Download System**
    - Unified logic for single and batch downloads via the new ZIP pipeline.
    - Cleaner controller/service split with new `OfferService`.

- **Data Model Adjustments**
    - Added `user_id` to clips to prepare for future ownership features.
    - Enforced uniqueness for user profile display names.

- **Logging & Observability**
    - Extended event and activity logging across all major systems.
    - More consistent context propagation for background jobs and asynchronous tasks.

### Fixed

- **Dropbox Upload Stability**
    - Resolved chunked upload issues (`lookup_failed/incorrect_offset`) and added stream rewinding.
    - Correct handling of small files (< chunk size) using direct upload.

- **Mail Processing**
    - Improved error handling in welcome mail listeners to prevent queue crashes.
    - Correct timestamp parsing and duplicate detection for inbound mails.

- **Preview Generation**
    - Fixed failures caused by mismatched FFmpeg configuration types and invalid dimension handling.

- **Video Model**
    - Safe preview path resolution when previews are missing.

- **Race Conditions**
    - Eliminated deadlocks during concurrent video ingestion via `firstOrCreate()` on unique hashes.

- **Configuration Rendering**
    - Boolean, JSON, and array values now display in consistently readable formats.

- **UI Issues**
    - Fixed footer overlapping in Filament upload views.

### Deprecated

- Legacy CSV parsing logic.
- Old Dropbox upload implementation.
- Old single-file download controller.

### Removed

- Legacy preview service and FFmpeg components replaced by the new modular engine.
- Old ingestion system and deprecated classes.
- Filament v3 dependencies and components.
- Mutable `$stats` arrays previously used in CSV import.
- All raw `fopen`, `unlink`, and direct filesystem operations in ingest and upload processes.
- Direct filesystem calls in preview generation.
- Legacy ingest scanner and all direct file I/O operations.

### Fixed

- **Inbound Mail Handling (Missing `to` Field)**
    - Fixed an issue in the `InboundMailHandler` where the `to` field was not properly passed,  
      causing a SQL exception and preventing inbound emails from being processed correctly.  
      The recipient address is now consistently persisted, ensuring reliable inbound mail collection.

- **Reply Handling Refactoring**
    - Moved the `shouldIgnore` logic for detecting automatically submitted replies  
      from the `MailReplyScanner` to the `ReplyHandler`, improving separation of concerns  
      and overall maintainability of the mail scanning workflow.
    - Automatic replies are now processed correctly, while auto-submitted messages  
      are safely ignored and flagged as seen.

## [3.0.0-beta.3] - 2025-11-03

### Added

- **Enhanced Logging & Observability**
    - Introduced extended activity and event logging across key application components,  
      improving traceability, auditability, and operational transparency.  
      The new logging layer provides consistent context propagation and structured output,  
      ensuring better insight into background processes and system-level events.
    - This foundation enables unified monitoring across synchronous and asynchronous workflows,  
      preparing the application for future real-time observability integrations and analytics.

- **Adaptive Video Compression**
    - Extended the `PreviewService::generatePreviewByDisk()` method with a new optional  
      `$autoCompression` parameter that enables automatic adjustment of FFmpeg settings  
      based on the original file size.
    - Introduced the internal helper method `applyAdaptiveCompression()`, which dynamically  
      tunes compression strength (`CRF 30–36`) and applies half-scaling for large videos (> 300 MB)  
      to significantly reduce output size while preserving visual quality.
    - Includes detailed logging of applied CRF values and scaling behavior,  
      providing full traceability of adaptive encoding decisions during preview generation.
    - Maintains full backward compatibility — adaptive compression is only active  
      when explicitly requested via the new parameter.

### Changed

- **FFmpeg Encoding Defaults**
    - Updated `ffmpeg_video_args` to use a consistent and safe downscaling filter.  
      The new filter  
      `"scale=trunc((if(gte(iw\\,2)\\,iw/2\\,iw))/2)*2:trunc((if(gte(ih\\,2)\\,ih/2\\,ih))/2)*2"`  
      safely halves both width and height while ensuring even output dimensions (divisible by 2).  
      This results in roughly 4× smaller frame size and improved encoding efficiency.
    - Added proper escaping for commas (`\\,`) to ensure FFmpeg correctly parses the filter expression.
    - Ensured that invalid or odd frame sizes automatically fall back to the nearest valid even dimensions.
    - Increased the default `ffmpeg_crf` value from 30 to 33 for stronger compression with minimal perceptible quality
      loss.
    - Maintained compatibility with `yuv420p` pixel format and `+faststart` flag for smooth progressive streaming.

- **Reminder Notification Logic**
    - Updated the `NotifyReminders` console command to respect the new configuration flag `email_reminder` under the
      `email` category.  
      Reminder notifications are now only queued when this setting is explicitly enabled, allowing flexible activation
      or deactivation  
      of automated reminder emails via configuration.
    - Introduced a new configuration key `email_reminder` through a dedicated database migration.  
      This flag determines whether reminder emails are sent at all and enables centralized control via the configuration
      system.
    - The `--days` CLI option and the existing `email_reminder_days` configuration remain available and continue to
      define  
      the lead time (in days) for reminder notifications before link expiration, ensuring full backward compatibility.

### Fixed

- **Video Upload Time Field Behavior**
    - Resolved an issue where the `start_sec` and `end_sec` inputs were editable before a video was uploaded,  
      leading to inconsistent or invalid timing data.
    - The inputs are now automatically disabled until a valid duration is detected and populated via Livewire.  
      Once the video metadata is processed, `end_sec` is prefilled based on the actual video duration.
    - Simplified frontend logic by removing redundant DOM manipulation for `end_sec`;  
      updates are now handled fully through reactive Filament state changes.

- **Video Insert Deadlocks under Concurrent Jobs**
    - Fixed a race condition causing `Lock wait timeout exceeded` errors when multiple ingestion jobs  
      attempted to insert identical video hashes concurrently.
    - The `create()` call in `VideoService::createVideoBydDiskAndFileInfoDto()` was replaced with `firstOrCreate()`,  
      ensuring atomic inserts and eliminating database-level contention on the unique `hash` constraint.
    - Added debug-level logging around insert operations to improve visibility into concurrent job behavior  
      and transaction timing during high-throughput ingestion.

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

- **Configuration Value Rendering**
    - Fixed an issue where boolean configuration values were displayed inconsistently (`1` or empty) instead of readable
      states.The rendering logic was refactored to use a `match` expression, ensuring clear and consistent output across
      all types: arrays are now shown as JSON, booleans as `'an'` / `'aus'`, and all other values as strings.
    - This improves both UI readability and debugging clarity when inspecting configuration data within the application.

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
