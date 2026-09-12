# Public frontend redesign

Date: 2026-09-12
Branch: `feature/homepage-redesign`
Status: design approved in conversation; written specification awaiting review.

## Objective and boundaries

Modernize the public DashClip Delivery product website while preserving its URLs,
real product behavior, existing logo, and framework architecture. The visual
direction is technical, clean, and restrained: a dark dashcam hero, orange actions,
and light editorial surfaces. The internal Admin and Standard panels are not being
redesigned.

The user approved the target design and requested a footer containing only channels
already known to the project, explicitly excluding Dashboard Heroes. Every channel
initially uses the same neutral symbol; channels may submit their own logos later.

Blog functionality is a separate future project. Allow space for an additional
navigation item and compatible article layouts, but do not implement blog routes,
models, management screens, cards, example posts, or inactive navigation links now.
Do not build a public clip gallery or expose submitted clips as marketing content.

## Verified baseline

- PHP 8.4.21, Laravel 13.26.1, Filament 5.7.6, Livewire 4.4.1.
- Tailwind 4.3.0, Vite 6.4.3, laravel-vite-plugin 1.3.0.
- Public pages use server-rendered Blade and `resources/views/layouts/app.blade.php`.
- Existing Vite inputs are `resources/css/app.css`, `resources/js/app.js`, and
  `resources/css/filament/admin/theme.css`.
- `app.css` uses Tailwind's CSS-first configuration. The legacy
  `tailwind.config.js` is not explicitly loaded by that stylesheet.
- Both panel providers also register `app.css`, `app.js`, and the shared footer.
  Changing these shared assets indiscriminately would affect the internal panels.
- Reusable structures include the cookie banner, video card, token action panel,
  footer, and `Page` content component backed by `PageService`.
- Public pages use system fonts; the standalone game loads Inter externally.
- Homepage metadata includes description, canonical, and OpenGraph. Its image URL
  currently includes a per-request timestamp. Other pages mostly supply titles.
- The existing robots file allows crawling; no sitemap implementation was found.
- Existing HTTP tests cover the cookie banner, API overview, offers, ZIP/downloads,
  and token actions. No installed browser runner was identified in the container.
- The local homepage returned HTTP 200. No browser accessibility or Core Web Vitals
  assessment has yet been performed.

## Binding guidance

Apply relevant ADRs before implementation: 0001 for behavioral test layers, 0002 for
names, 0003 for responsibility boundaries and framework extension points, 0004 for
domain exception contracts where applicable, 0005 for public PHPDoc contracts,
0006 for changelog maintenance, and 0007 for Conventional Commits. ADR 0008 remains
applicable if API documentation behavior is touched; this redesign does not change
API operations.

Run all project commands inside `docker compose exec sharing`. Verify installed
package APIs before using them. Do not modify vendors, add dependencies without
authorization, introduce a second frontend framework, or redesign the panels.

## Visual references and assets

Primary references are `tmp/mockup-desktop.png` and `tmp/mockup-responsive.png`.
They define composition, spacing, color balance, and responsive priorities, not
literal product content or fixed screen coordinates.

Use the existing `public/images/logo.png`, preserving its artwork and proportions.
Create smaller delivery variants without inventing or replacing the mark. Preserve
existing favicons. Do not adopt the different logo visible in the mockups.

The user approved the generated, text-free motorway sunset image as the hero:
`/home/ilya/.codex/generated_images/01a096fd-eb4f-7421-a22d-f5cceed30f92/exec-b1f267e9-be42-433a-8f45-2de3e906ff71.png`.
During implementation, copy it into project-owned assets and produce responsive
delivery sizes with the existing image tooling. Production must not depend on the
personal generated-images directory. Keep provenance of this synthetic decorative
image; do not describe it as a real submitted clip.

Hero copy, navigation, and actions are semantic HTML over the image. No baked-in
text, mock player controls, timestamps, speed indicators, or invented logos.
Use an image crop that retains the road on narrow screens and darkens the text
area sufficiently for contrast. The hero image is decorative because the HTML
explains the product; use empty alternative text for its image element.

## Information architecture

The homepage contains the following sections in document order:

1. Header: existing identity, primary navigation, login, prominent registration.
2. Hero: headline, brief product explanation, upload entry, process anchor.
3. Benefits: single upload, automatic distribution, previews/downloads, traceability.
4. Process: register, upload, distribute to suitable channels.
5. Submitters and channels: explain their distinct roles and existing entry points.
6. Offers: explain limited availability, single/ZIP downloads, and returning clips.
7. Help: concise questions and answers derived from actual documented behavior.
8. Closing registration/login call to action.
9. Footer with known channels and existing legal/project links.

Hero heading: "Deine Dashcam-Clips. Mehr Reichweite." Supporting copy describes
one upload and distribution to multiple suitable channels. Do not guarantee
publication or audience growth. Do not add unsupported pricing, certification,
security guarantees, testimonials, statistics, integrations, or channel claims.

Primary navigation: Home, So funktioniert's, Kanäle, Angebote, Hilfe. Informational
items target meaningful homepage anchors; links from other pages include the
homepage path. Blog is added only when the later blog project supplies real pages.
No public channel directory or user-data enumeration is introduced.

The primary hero link reads "Clips hochladen" and uses the existing Standard-panel
registration route for guests. Keep the existing login route obvious. Do not
create a new upload or authentication workflow. The secondary hero action points
to the process section, not to a fake video playback interaction.

## Footer channel content

Use this explicit, curated list of project-known names:

- RLP Dashcam
- Lets Dashcam
- Augen auf!
- Road Rave Germany
- NEDK - NOCH EIN DASHCAM KANAL
- Dashcam Stories

Source: the existing channel records in
`database/migrations/2025_08_08_094613_create_channels_table.php`.
This is evidence of names already known to the project, not verification of a
current partnership. Use a neutral "Kanäle" heading. Do not claim endorsements,
current participation, or partner status. Do not infer YouTube URLs from names;
display text until an exact destination is verified. Do not publish email
addresses, personal contact names, internal IDs, or arbitrary database records.

DashboardHeroes / Dashboard Heroes is explicitly excluded regardless of its
presence in historical records. Mockup-only channel names are not imported.
Use the same neutral video-camera icon from the installed Heroicons set for every
channel, accompanied by its text name. The symbol is decorative and hidden from
assistive technology because the adjacent name identifies the channel. Do not
invent or imitate individual channel identities or generate substitute brand logos.
Keep the icon slot replaceable by a submitted logo in a later project; do not build
logo upload, storage, approval, or channel self-service functionality now. Maintain
this curated presentation data in one place within the public footer implementation,
not in historical migrations.

Keep links for imprint, privacy, terms, license, API overview, changelog, roadmap,
issue reporting, and the project repository. Preserve the non-production notice
and existing version information. Internal panels retain their existing compact
footer presentation; the expanded channel footer belongs to the public website.

## Public pages and functional preservation

- `/`, `/impressum`, `/datenschutz`, `/tos`, and `/api-docs` share the refreshed
  public layout. Legal text is not substantively rewritten by this task.
- `/changelog` and `/license` retain their paths and real source content but gain
  complete document markup, the shared frame, and readable typography.
- `/offer/{batch}/{channel}` and its unused-video pages retain signatures, forms,
  selection, download interactions, and return behavior. Apply a compact public
  layout variant, without a marketing hero. Keep existing DOM integration points
  where client behavior depends on them, or change both sides with behavioral tests.
- Token confirmation pages retain their existing GET/POST distinction and actions.
- Preserve Dropbox, download/progress, storage, auth/register, and API routes.
- Public error pages use the shared visual language, correct status codes, useful
  recovery links, and reduced-motion behavior.
- `/game` remains available. Integrate its presentation appropriately without
  changing gameplay; drawing/gameplay may continue to require JavaScript.
- The API overview is in scope; vendor-generated interactive documentation keeps
  its supported presentation and working links. Do not override vendor templates
  merely to imitate the marketing site.

## Responsive design and accessibility

Use Tailwind's standard responsive utilities and normal document flow. Use fluid
content widths, flex wrapping, and explicit grids instead of global layout-class
overrides. Decorative overlays may be positioned; content must not rely on absolute
coordinates. Cards move from one to two or more columns as space permits.

Stack hero actions on narrow screens. Avoid fixed hero heights, clipped headlines,
forced line breaks that fail at intermediate widths, and horizontal overflow.
Keep the same content order across desktop and mobile.

Provide a mobile navigation disclosure using native semantics with a no-JavaScript
path to every link. Enhance keyboard behavior only where useful. Navigation must
remain usable with text enlargement, long labels, and keyboard input. Include a
skip link, visible focus, understandable control names, and at least 44px primary
touch targets. Aim for WCAG AA text contrast and clear non-color-only states.

Use one meaningful h1 per page and ordered heading levels. Use header, nav, main,
section, article, and footer where appropriate. Buttons perform actions; anchors
navigate. Avoid interactive controls nested inside labels or other controls.
Associate validation messages with forms and announce relevant status changes.

Keep the existing theme preference and `theme` localStorage value compatible.
The default is a dark hero with light content, matching the mockups. A saved theme
is respected. Give the theme control an accessible name and usable fallback when
storage access fails. Limit transitions and honor reduced motion.

Retain the necessary-cookie notice and its existing consent cookie behavior.
Make it responsive and avoid obscuring keyboard focus or primary content. It is
an informational notice, not a new tracking-consent system. No analytics or tracking
integration was found; none is introduced by this redesign.

## Components, CSS, and JavaScript

Continue using the existing public Blade layout with explicit page variants and
small reusable components for navigation, buttons/CTA links, repeated benefit cards,
section headings, metadata, and the public footer. Keep single-use sections local
unless they have an independent responsibility. Preserve useful existing content,
video, and token components rather than duplicating their behavior.

Add public asset inputs through the same Vite pipeline to isolate marketing CSS
from the legacy shared panel assets. Keep the panel theme and behavior stable.
Use CSS-first Tailwind tokens, existing standard breakpoints, and utilities.
Do not introduce another CSS framework or broad global overrides.

The public entry must not redefine Tailwind's `.grid` or `.container`. Replace their
legacy implicit sizing with explicit layout utilities on affected public views.
Use official theme/variant extension points, not `!important` or dependency patches.
The old JavaScript Tailwind config is not treated as active configuration; remove
obsolete project files only after checking all consumers. Dependency removal is
not implied by this design.

Marketing pages do not need Echo, Pusher, Axios, or ZIP-download logic. Keep those
modules available for offers and panel interactions using page-specific loading
through the existing bundler. Preserve initialization order for download behavior.
Move relevant inline UI scripts/styles to maintained components or asset modules.
Do not make marketing content dependent on Livewire hydration.

## SEO and performance

Render informational content and navigation on the server. Centralize per-page
title, description, canonical, OpenGraph, and social-card output, avoiding duplicates.
Use stable absolute image URLs, with no time-based query parameters. Canonical URLs
use the intended public origin and omit unrelated tracking parameters.

Sensitive signed offers and token pages use noindex and do not emit token-bearing
URLs in canonical or social metadata. Keep access checks intact: indexing policy
is not an authorization mechanism. Error pages also use appropriate indexing policy.
Preserve the existing robots behavior. Do not add sensitive routes to a sitemap;
if a sitemap is later introduced, only public indexable canonical pages qualify.
No new sitemap subsystem or speculative structured data is required for this scope.

Supply responsive hero/logo assets with correct intrinsic dimensions and stable
aspect ratios. Load the hero eagerly with appropriate priority. Lazy-load suitable
below-the-fold images. Avoid duplicated image downloads, full-size logos at small
display sizes, autoplay video, heavy animation, and extra webfonts. System fonts
are the public default; replace the game's external font request with that stack.

## Verification and acceptance

Use `superpowers:verification-before-completion` before reporting completion.
All commands run in the sharing container. Do not install new quality dependencies
without authorization. The implementation plan must resolve a browser verification
method using available tooling, or report the specific blocked checks honestly.

Required automated checks:

- Existing and new relevant HTTP tests, then the complete test suite with parallel
  execution. Cover route preservation, login/register links, footer exclusions,
  metadata, cookie behavior, and affected forms and download interactions.
- Test behavior, not exact utility-class strings or cosmetic DOM structure.
- Production Vite build, including Tailwind compilation; inspect warnings.
- Existing formatting/static checks applicable to touched files, including Pint
  for PHP changes. Inspect configured checks rather than inventing new tools.

Required browser checks:

- Small and normal phones, tablets, small laptops, desktop, and large screens,
  including intermediate widths and text zoom.
- Keyboard navigation, focus visibility, navigation disclosure, theme persistence,
  cookie notice, contrast, heading structure, and no-JavaScript informational access.
- No unexpected console errors, missing assets, broken internal links, accidental
  horizontal overflow, text clipping, or obvious layout shifts.
- Inspect metadata and image dimensions on representative page types. Assess LCP,
  CLS, and interaction behavior; distinguish local measurements from field data.
- Smoke-check existing panel styles and ZIP behavior because shared assets and
  footer integration are known regression boundaries.

The final report lists changed areas, reused/new components, removed workarounds,
SEO/accessibility/responsive/performance decisions, actual checks and builds, and
remaining limitations. No check may be described as passed without execution.

## Workflow handoff

After the user reviews this written specification, use
`superpowers:writing-plans` to produce a concrete implementation plan. Implementation
starts only after that plan is agreed. Use the applicable Superpowers development,
testing, debugging, review, and completion workflows during execution.
