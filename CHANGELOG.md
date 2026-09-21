# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Preferred channel when uploading**: The upload form offers an optional channel choice, so the
  wish no longer has to be set through the eighth column of `info.csv`. Only channels the upload can
  actually reach are listed, paused channels are left out, and the chosen channel is shown in the
  video details.
- **Channel Operator API**: Channel settings can be changed through the API like on the channel
  settings page. `PATCH /api/v1/channels/{channel}` now accepts name, creator name, email, YouTube
  name, paused video reception and the homepage listing, each optional; name and email must stay set
  and unique. `POST /api/v1/channels/{channel}/logo` uploads a logo (PNG, JPEG or WebP, up to
  512 KB, shrunk to fit 256 x 256 pixels) and `DELETE` removes it. Channel responses include
  `show_on_homepage` and `logo_url`. The Channel Operator API documentation is now version 1.1.0.

### Changed
- **Development container**: The image library now supports WebP.
- **Faster automated checks**: The coverage run on the core branches measures with PCOV instead
  of Xdebug, which is several times faster; the nightly coverage run keeps Xdebug as reference. The
  editorial permission seeder creates and grants its permissions in one pass instead of reloading
  all permissions for each one, which saves about a second in every database test.
- **Offer downloads on "Meine Angebote"**: Downloads are prepared in the background and delivered
  through a notification with a "ZIP herunterladen" button instead of the progress dialog. The ZIP
  still contains the selected videos and their `info.csv`; unavailable videos are skipped and named
  in the notification. Prepared downloads are kept for one day; opening an expired link afterwards
  explains that instead of showing an error page. The dialog speaks of offers instead of internal
  record names.

### Security
- **Packages**
    - upgraded packages to newest version (e.g. laravel or npm dependencies)

## [4.10.0] - 2026-09-20

### Added
- **Privacy policy**: Name the storage provider as processor in a new section, set in the
  administration settings. The section about the previous storage provider disappears once no videos
  are stored there any more and new uploads go elsewhere.
- **Storage at Hetzner**: A new SFTP storage target and the `storage:migrate-videos` command, which
  copies videos to another storage, verifies each copy and only then switches the video over. Runs
  can be repeated, support a dry run, single videos and a limit, and keep the original files.

### Changed
- **Search engines and link previews**: Describe the organization, website and articles with
  structured data, link the German and English versions of the blog overview, categories and tags,
  and name the site and language in link previews. The blog language switcher on a category or tag
  page now leads to the same category or tag in the other language.
- **PHP 8.5**: Require PHP 8.5. The development container, the automated checks and the deployment
  template now use PHP 8.5; servers must provide PHP 8.5 before deploying this version.
- **Offer downloads**: A video that is missing, cannot be read or is no longer offered no longer
  cancels the whole ZIP. It is skipped and shown as skipped in the download dialog, left out of
  `info.csv`, not marked as downloaded, and recorded with its reason in the application log. The
  download only fails when none of the selected videos can be delivered.
- **Storage setting**: The default storage setting can name any configured remote storage, so new
  uploads can be moved there instead of only to Dropbox. Local values still keep uploads in place.
- **Dependencies**: The OAuth library is held on its 13.7 release line so that the SFTP storage can be
  used; that line has no known security advisories.
- **Video migration**: The migration command checks both storages before the first video and stops
  with the real cause, for example a rejected login, instead of failing once per video. Failures now
  name the underlying cause in the output and in the log.

### Fixed
- **Blog pagination**: Later overview pages are no longer marked as duplicates of the first page for
  search engines.
- **Offer downloads**: Deliver a single selected video as a ZIP again, including its `info.csv` with
  the clip details; the direct video link remains available as a fallback. The download progress now
  advances while a large video is transferred instead of staying at 0% until it is complete.
- **Channel applications**: Record the approval of a channel application in the activity log.
  The entry was prepared but never saved.
- **Offer downloads**: A closed download dialog no longer reappears when the offers page is reloaded.
- **Offer downloads**: The "Heruntergeladen" tab lists every offer once and shows only its latest
  download time, instead of one row and one time per download. Sorting by download time works in
  both directions.
- **Offer downloads**: Videos on storage other than Dropbox or the local disk are transferred before
  packing, so ZIP downloads no longer skip them.

## [4.9.0] - 2026-09-18

### Added
- **Passkeys**: Sign in with a passkey from the login page, or use one as a second sign-in step.
  Add, rename and remove passkeys in the account profile.
- **Release news**: Publish a German and English news article with every version that explains the
  changes for submitters and channel operators, starting with 4.9.0. It goes live with the
  deployment.

### Changed
- **Account profile**: Group personal details, sign-in settings and notifications into translated
  sections using the shared panel design.
- **Account profile**: Open the profile inside the regular user area with the full navigation,
  scoped to the user's own team.

### Fixed
- **Team channels**: Hide channels with paused video reception from the team channel list and only
  offer channels that currently accept videos when assigning a channel to a team.

### Security
- **Account profile**: Require the current password before changing an email address or password.

## [4.8.0] - 2026-09-16

### Added
- **Weekly blog article queue**
    - schedule 67 bilingual articles, one per week starting a week after the seeder runs, through
      the new `BlogEditorialQueueSeeder`; the existing publication command releases each article
      on its date.
    - cover each article with its own artwork, copied to the public disk on the first run and left
      untouched where an editor has replaced it.
    - rebuild the artwork from the committed sources with
      `node database/seeders/data/images/build-covers.mjs`.

### Changed
- **Admin panel design**
    - adopt the shared dark navigation, orange accents, homepage branding, translucent support
      card and responsive login design while retaining the admin resources and widgets.
    - share login views and guest language selection across both panels without adding admin registration.
- **Standard panel login**
    - add a responsive split-screen sign-in page with the homepage branding, a highway image,
      translated guidance and a persistent guest language selector.
    - adapt spacing to viewport height and use a compact mobile layout to avoid unnecessary
      scrolling while allowing overflow for small landscape screens and enlarged text.
    - retain Filament's authentication, password recovery and multi-factor challenge forms.
- **Standard panel redesign**
    - introduce a dark sidebar, orange accents, responsive dashboard and dedicated panel theme
      with a shared stylesheet that other panels can adopt.
    - show team-scoped video and download statistics, recent footage, authorized shortcuts and
      setup progress in German and English, with support for dark mode.
    - reuse the homepage logo, add a subtly translucent support card, hide the sidebar scrollbar
      without disabling scrolling, and replace the old welcome widget with the new dashboard.
    - resolve the panel logo link to the current team's dashboard instead of a relative URL.

### Fixed
- **Offer downloads**
    - download single videos directly and prepare multi-video ZIPs without requiring WebSockets;
      retain individual video links when ZIP preparation fails or the queue is unavailable.
    - poll resumable preparation status, preserve links across page reloads, and hand files to the
      browser without buffering the entire archive in JavaScript.
    - isolate concurrent ZIP requests, report incomplete archives as failures, and retain archives
      for retries with automatic cleanup after two days.
    - keep queue reservations longer than the application's job timeouts to prevent concurrent retries
      during long ZIP builds and video processing.
    - validate signed preparation, status and file URLs and recheck individual offer availability.
- **My Offers**: Prevent a preview error when an offer references a deleted video.

## [4.7.0] - 2026-09-15

### Added
- **Operator API downloads**
    - download offered videos through `GET /api/v1/offers/{offer}/download` with the dedicated
      `offers:download` scope, channel access checks, expiry checks and download tracking.
    - register the new scope by running the idempotent `PassportScopeTaxonomySeeder` on deployment.

### Changed
- **Blog preview on the start page**
    - show the five newest articles instead of three, and move the section above the process
      description so it is seen without scrolling past the product sections.
    - the amount is configurable and is part of the cache key, so a changed value takes effect
      instead of serving the previously cached selection.
    - present the articles as flat image tiles with the heading on top instead of full cards, so the
      row stays a teaser and no longer dominates the page.
- **Panel configuration**
    - share the common panel middleware stack between the administration and the standard panel
      instead of duplicating it in both panel configurations.
    - cover the resulting middleware stack with tests so a missing or misreferenced middleware
      class is detected before deployment.

### Fixed
- **Scheduled mailbox scan during mailbox outages**
    - treat an unreachable mailbox as a temporary condition: the scheduled reply scan now logs the
      failure and completes instead of aborting with an error on every run.
    - translate transport and folder failures into dedicated mail exceptions, and report a missing
      inbox folder explicitly instead of failing on an empty result.
- **Filament log viewer compatibility**
    - exclude the log viewer's detail page from Shield's permission label discovery so the current
      log viewer release can initialize its record during mount without causing admin pages to fail.
- **Inactivity reminders for active sessions**
    - track authenticated panel activity, including interactive panel updates, at five-minute
      intervals so users with long-lived sessions are not incorrectly reminded based on an old login.
    - preserve the last login timestamp separately; deployment requires the last activity migration.

### Security
- **Packages**
    - upgraded packages to newest version (e.g. laravel or npm dependencies)

## [4.6.1] - 2026-09-14

### Fixed
- **Inactivity reminders for existing accounts**
    - include inactive accounts without a recorded login using their account creation date,
      and send a reminder without inventing a historical login date.
- **Admin user login activity**
    - display the last login in the sortable user table and the user edit page,
      with a translated placeholder for users without a recorded login.
    - show the recorded inactivity reminder timestamp as read-only information on the user edit page.

## [4.6.0] - 2026-09-14

### Added
- **Dynamic XML sitemap**
    - added `/sitemap.xml` for public pages and current German/English blog content,
      advertised through a dynamically generated `robots.txt`.
    - exclude unpublished, non-indexable and non-canonical articles; article modification
      dates reflect stored content changes, and the existing blog sitemap shares the same source.
- **Editorial blog**
    - added German and English article pages, search, categories, tags, RSS feeds,
      a sitemap, and the latest published articles on the homepage.
    - added permission-controlled editorial forms with translated content, media,
      search-engine metadata, previews, duplication, and scheduled publication.
    - added safe Markdown rendering with note and warning callouts, cached public
      aggregates, responsive article cards, and plain sharing links without trackers.
    - added an initial content seeder with three bilingual categories, five tags
      and a complete German/English introduction; repeated runs preserve editorial
      changes and deletions through a transactional, persistent execution marker.
- **Channel operators maintain their own public presentation**
    - the channel area now carries the homepage visibility switch, which previously
      only an administrator could set.
    - channel operators can upload a logo for the public channel list. It is shown at
      the same size as the previous neutral symbol, which remains the fallback.
    - both changes are recorded in the channel history, so it stays traceable who
      changed the public presentation and when.

### Changed
- **Blog test coverage**
    - cover editorial duplication through its admin action, tag editing, taxonomy
      ownership, article image fallbacks and missing-translation diagnostics.
- **Shared public components**
    - centralized language option styling for blog links and public language buttons
      within the existing shared header, keeping both variants visually consistent.

### Fixed
- **Blog language selection**
    - restored the active language background and outline on blog pages and aligned
      language links with the buttons used throughout the public website.
- **Blog administration labels**
    - corrected explicit singular and plural labels for posts, categories and tags
      in German and English, including page headings and create actions.
    - translated editorial field and icon labels, and show localized category and
      tag names in article forms and lists with a slug fallback for missing translations.
- **Unreadable mail content in the mail log**
    - incoming mail was stored exactly as it arrived, so the log showed transport
      structure or encoded blocks instead of the message. It is now decoded while
      reading, preferring the formatted part over the plain one.
    - the content view no longer places a mail into the administration document.
      It is shown in an isolated frame, which also repairs outgoing mail, whose
      complete documents could never render correctly inside a panel.
    - remote images are held back until they are explicitly requested, so opening
      an entry no longer confirms the read to the sender.
    - a maintenance command repairs entries that were stored before this fix.
    - reading a mail without a usable header no longer aborts the scan, and a
      stray debug output was removed from that path.
- **Missing API documentation after a deployment**
    - the deployment now regenerates the interface specifications, which previously
      existed only as a defined step that nothing ever invoked.
    - the specifications are versioned instead of ignored, and are regenerated on
      request by default so an environment can never serve an empty documentation page.

## [4.5.0] - 2026-09-13

### Added
- **Public channel visibility**
    - linked listed channel names to their YouTube channel using the stored handle.
    - displayed readable channel names without underscores or permanent link underlines.
    - added an admin checkbox controlling inclusion in the public channel list,
      enabled by default for existing and new channels.
    - replaced hardcoded channel names with an alphabetical database-backed list;
      the channel section is hidden when no channels are enabled.
- **Shared email design**
    - aligned transactional emails and Markdown notifications with the public
      website through a reusable layout, branded header, footer, and action button.
    - removed duplicated document wrappers while preserving message content and links.
- **Public language selection and platform guide**
    - added German and English language buttons with a persistent explicit choice
      and automatic browser-language detection on public routes.
    - translated the homepage and shared public navigation, footer, and cookie
      notice; panel account language preferences retain their existing behavior.
    - identified remaining German-only page content with a language notice and
      explicit language markup for assistive technology.
    - added a plain-language walkthrough from upload to the submitter’s choice of channels,
      downloads, returns, and publication decisions in both languages.
    - clarified that channel operators first register a regular account and then
      apply for channel operator access from within their account.
    - labelled the footer channel list as a selection of participating channels.
    - kept the cookie notice visible at the bottom of the viewport until dismissed,
      with responsive spacing so the footer remains reachable.
- **Public website design specification**
    - documented the responsive redesign scope, preservation of existing public
      functions, and requirements for accessibility, metadata, and asset delivery.
    - reserved the blog for a separate project and excluded a public clip gallery.
- **REST API core resources (v1)**
    - new authenticated REST endpoints under `/api/v1` for videos (list, detail, multipart
      upload through the existing ingest pipeline, rename, delete), channels (list, detail,
      pause/resume video reception), offers (list, detail, create, comment) and teams
      (list, detail, create, delete), all scoped to the data the authenticated user already
      sees in the Standard panel.
    - video deletion enforces the same rule as the Standard panel: a video with active or
      already picked-up offers cannot be deleted (409). The video processing status is
      exposed read-only for progress polling but is not a filter.
    - filtering (`filter[...]`), sorting (`sort=`) and pagination (`page[number]`,
      `page[size]`, `meta.pagination` response block) on all list endpoints.
    - OAuth2 scope taxonomy (`videos:*`, `channels:*`, `offers:*`, `teams:*`) seeded for the
      self-service client UI; per-route scope enforcement via middleware.
    - interactive OpenAPI 3.0 documentation, split into three: Authentication
      (`/api/documentation/authentication`: the OAuth2 token/authorize/device endpoints),
      Submitter API (`/api/documentation/submitter`: videos, teams) and Channel Operator API
      (`/api/documentation/channel-operator`: channels, offers). Each resource API declares
      only the scopes it needs. An `authorizationCode` OAuth2 flow and a bearer-token scheme
      are wired up so endpoints can be tried out directly; the other grant types (personal
      access token, device, client credentials) are described in the security scheme. Every
      operation carries a behavioural description, not just a title (ADR 0008).
    - new `/api-docs` overview page (linked in the site footer) that lists every documentation
      set (Authentication first as the entry point, then the resource APIs) with its Swagger
      UI and raw-spec URLs.
- **REST API OAuth2 foundation**
    - Passport is wired up end-to-end: bearer-token authentication via a new `api` guard,
      verified through a `GET /api/user` sanity-check endpoint.
    - Standard-panel users can now self-manage their own OAuth clients and personal access
      tokens with their assigned scopes; the admin panel retains full client/token management.
    - This is the foundation sub-project for the broader REST API (Ticket #250); the actual
      domain endpoints follow in a later sub-project.
- **Deploy: Passport key provisioning**
    - deploys now run `passport:keys` (without `--force`) after installing vendors, so the
      OAuth encryption keys exist in the shared `storage` directory from the first deploy
      onward; the command is idempotent and never overwrites keys that already exist, so
      previously issued tokens/sessions stay valid.
- **`preferred_channel` column in `info.csv`**
    - submitters can add an optional `preferred_channel` column (channel name or numeric id) to
      route a video to a specific channel. On import an unknown or paused channel logs a warning
      and the video falls back to the normal distribution algorithm.
    - the distributor honours a valid preference without overriding weekly quotas, channel blocks
      or team-channel scope; a wished channel that is temporarily out of quota defers the video to
      the next run instead of reassigning it elsewhere.
    - the admin assignment overview gains a "Preferred channel" column and filter showing whether
      an assignment was made via `preferred_channel` (Ticket #139).

### Changed
- **Public website redesign**
    - replaced the previous public website design with a complete responsive
      redesign of the homepage and shared public page layout.
    - introduced a consistent visual identity with a dark navy brand header,
      orange accents, light content surfaces, and reusable public UI components.
    - redesigned the homepage with upload entry points, responsive navigation,
      a dashcam hero, process guidance, help, and six curated channel names.
    - unified public information, error, offer, and confirmation pages with
      accessible controls, theme preferences, and stable page metadata.
    - isolated public assets from panel assets, optimized delivery images,
      and limited game and download scripts to the pages that use them.
    - signed offers and token pages omit canonical and social URLs and request
      no indexing; download selection excludes already downloaded clips.
- **Frontend review tooling**
    - separated game updates into movement, timing, and collection steps while
      preserving gameplay behavior.
    - removed temporary browser reports and screenshots from version control;
      retained the approved design references and production assets.
    - excluded Docker data and temporary files from formatting discovery so the
      standard dirty-file formatter works without database-directory access.

### Fixed
- **Passport migration order**
    - deferred the scope grant client foreign key until after OAuth clients exist,
      allowing Passport installation on existing MySQL/MariaDB databases.
    - made the interrupted client-column migration retryable without dropping
      existing scope grants; already installed foreign keys remain intact.
- **Public page tests**
    - initialized the database for locale and API overview tests now that the
      shared footer reads channel visibility from the database.
- **Scope selection in the Standard panel came back and now follows the user's role.** The
  self-service client wizard offered no permissions at all, because the underlying package
  restricted the choice to scopes the user already held as their own grants, and nothing ever
  created those. Which scopes a user may put on their own client is now answered by the
  application: `RoleBoundScopeResolver` reads the scope and the Standard-guard permission that
  each `/api/v1` route already declares, so a channel operator can pick channel and offer
  scopes, a regular user video scopes, and the list can never drift from what the endpoints
  actually enforce.
- **OAuth client creation in the Standard and admin panel** was impossible: every attempt
  aborted with a database error and no client secret was ever shown. The
  `passport_scope_grants.context_client_id` column was created as a bigint by an older
  version of the scope-grant migration, while client ids are UUIDs. MySQL/MariaDB rejects
  the UUID with "Data truncated for column 'context_client_id'", which killed the request
  before the page that reveals the generated secret could load. A repair migration recreates
  the column with the correct type and restores its foreign key and indexes. Databases that
  already have the correct column are left untouched. A schema test now asserts that the
  column keeps the same type as the client primary key it references, which SQLite alone
  cannot catch because it does not enforce column types.

## [4.4.0] - 2026-08-20

### Added
- **Download history (Standard panel)**
    - new, read-only page under "My Media" that shows, per download event, which of the
      user's own videos was downloaded by which channel; links directly to the video entry.
- **Last-login tracking & inactivity reminder**
    - the app now records each user's last login timestamp (across both the admin and standard
      panels); users who have not logged in for 7+ days receive a reminder email, repeated every
      7 days while inactivity continues, resettable by logging in again, and toggleable per-user
      in the profile's "Notifications per Mail" settings like any other notification.
- **User language preference**
    - users can now pick a preferred language in their profile; it is applied to both the Admin
      and Standard panel UI and to outgoing mail/notifications, with a "system default" option
      when left unset.

### Changed
- **Developer workflow**
    - `php artisan test` is now documented consistently with `--parallel` in AGENTS.md/CONTRIBUTING.md,
      matching the CI configuration.

### Security
- **Packages**
    - upgraded packages to newest version (e.g. laravel or npm dependencies)

## [4.3.2] - 2026-08-15

### Security
- **Packages**
    - upgraded packages to newest version (e.g. laravel or npm dependencies)

## [4.3.1] - 2026-08-02

### Fixed

- **Cookie consent banner**
    - banner reappeared on every page load because Laravel's `EncryptCookies` middleware
      discarded the JavaScript-set cookie as undecryptable; `cookie_consent` is now excluded
      from encryption so the consent is correctly persisted across requests.

## [4.3.0] - 2026-08-02

### Added

- **Channel video reception paused notification**
    - sends the channel owner an email when an admin pauses video reception for their channel,
      including a two-step reactivation link (confirm page → submit) backed by the existing
      ActionToken system with a one-month expiry and automatic deduplication of outstanding tokens.

### Changed

- **Legal pages updated to current statutes**
    - Impressum: references updated from TMG to DDG (§§ 7–10, in force since 14 May 2024);
      added standard liability clauses for content awareness and link integrity.
    - Datenschutz: cookie legal basis updated to § 25 Abs. 2 Nr. 2 TDDDG (TTDSG successor);
      Dropbox transfer basis updated to the EU-US Data Privacy Framework (adequacy decision
      10 July 2023); added reception-paused notification to the email processing list;
      added AI systems section clarifying no AI-generated content is served to users
      (ref. Art. 50 EU AI Act, applicable from 2 August 2026).

## [4.2.0] - 2026-08-01

### Security
- **Packages**
    - upgraded packages to newest version (e.g. laravel or npm dependencies)

## [4.1.1] - 2026-07-25

### Fixed

- **Admin activity log**
    - fixed activity properties failing to render after the Filament 5.7 update by normalizing
      property values into displayable text.

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
    - replaced the experimental video upload page title, subheading, and bundle ID helper
      text with clearer upload guidance.

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
    - fixed assignment expiration calculation when cached configuration values for the
      default TTL are stored as strings.

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
