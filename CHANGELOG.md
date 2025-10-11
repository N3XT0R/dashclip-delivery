# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - undefined

### Added

- **Multi-factor authentication (MFA)** with support for authenticator apps (TOTP) and one-time codes via email.
- **Web-based video upload** in the admin UI, complementing existing ingest workflows.
- Config table now supports a `selectable` JSON column so settings can offer predefined choices.
- New **FFMPEG** configuration category seeds codec, preset and parameter defaults for preview generation.
- Admin UI renders selectable values for JSON-based settings as multi-select inputs.

### Changed

- **Backend upgraded to Filament v4** (UI components and pages migrated).
- Preview generation now uses the `pbmedia/laravel-ffmpeg` package and reads all codec options from the database.
- **Livewire Upload Configuration**
    - Increased maximum upload size to **1 GB** to support large video files.
    - Extended maximum upload time to **25 minutes**, corresponding to a required minimum upload speed of ~**5.5 Mbit/s
      **.
    - Updated Livewire configuration (`config/livewire.php`) to reflect new limits for smoother large uploads.

### Breaking

- The Filament v4 upgrade may require adjustments to custom admin pages, widgets, or themes.

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
