# Public Frontend Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task. Steps use checkbox syntax for tracking.

**Goal:** Deliver the approved public website redesign without changing panel design or existing business workflows.

**Architecture:** Extend `layouts.app` with public-only Vite inputs and reusable Blade components. Keep existing panel assets intact and load ZIP behavior on offer pages only. Preserve existing routes and content sources.

**Tech Stack:** PHP 8.4.21, Laravel 13.26.1, Blade, Tailwind 4.3.0, Vite 6.4.3; Filament 5.7.6 and Livewire 4.4.1 remain panel infrastructure.

**Spec:** `docs/superpowers/specs/2026-09-12-public-frontend-redesign-design.md`

## Global Constraints

- Run all project commands inside `docker compose exec sharing`.
- The internal Admin and Standard panels are not being redesigned.
- Do not build a public clip gallery or expose submitted clips as marketing content.
- Blog functionality is a separate future project.
- DashboardHeroes / Dashboard Heroes is explicitly excluded.
- Use the same neutral video-camera icon from the installed Heroicons set for every channel.
- Preserve the existing logo artwork, existing URLs, signatures, auth, downloads and return behavior.
- No dependencies, vendor edits, invented claims, new logos, or em dashes.
- Apply ADRs 0001-0007. PHPUnit behavioral tests, no PHPUnit mocks/stubs.
- Use real server-rendered content, one meaningful h1 per page and accessible native controls.
- Keep the existing theme preference and `theme` localStorage value compatible.
- Commit explicit files with Conventional Commits and push each commit on this feature branch.

## File map and interfaces

- `resources/css/public.css`: isolated public design tokens, foundational styles,
  editorial typography and temporary compatibility for existing public components.
- `resources/js/public.js`: theme, navigation, necessary-cookie notice only.
- `resources/views/layouts/app.blade.php`: common public document, metadata,
  navigation, main, flash messages, footer and optional page assets.
- `resources/views/components/public/{navigation,footer,metadata,button,section-heading,feature-card}.blade.php`:
  presentational reusable components. Additional small components only for real reuse.
- `resources/views/welcome.blade.php` and homepage partials if needed: approved sections.
- `public/images/marketing/`: approved hero and optimized logo delivery images.
- `resources/views/components/cookie-banner.blade.php`: existing cookie behavior, responsive notice.
- `resources/views/{impressum,datenschutz,tos,api-docs,game}.blade.php`,
  `resources/views/errors/base.blade.php`, new `resources/views/document.blade.php`:
  public supporting pages. Game JS/CSS move to `resources/js/game.js` and `resources/css/game.css`.
- `app/Http/Controllers/PublicDocumentController.php` and
  `app/Services/PublicDocumentService.php`: fixed changelog/license content only.
- `resources/views/{offer,tokens,channels}/`, `resources/views/components/{video-card,token-action-panel}.blade.php`:
  transactional public presentation, preserving current behavior.
- `resources/js/offers.js`: current offer download initialization only, uses existing downloader modules.
- `tests/Feature/Http/{PublicWebsiteTest,PublicDocumentTest,PublicTransactionalPagesTest}.php`:
  HTTP contracts for the three implementation tasks.

Layout interface: existing `title`, `subtitle`, `actions`, `head`, `content` sections
and style/script stacks remain available. New sections: `description` (string),
`robots` (string, default index/follow), `full_width` (truthy for homepage),
`page_assets` (Blade Vite directive for page-specific entries). Metadata automatically
omits canonical/social URLs for signed/token routes and error responses, using a
single decision in the layout or metadata component rather than duplicated checks.
`x-public.button` accepts `href` for a link or defaults to `type=button`, `variant`
(primary/secondary), plus standard attributes. `x-public.section-heading` accepts
eyebrow/title text and a default explanatory slot. Choose consistent prop names
and document any additional interface in the implementation report.

### Task 1: Public frame, homepage and isolated assets

**Files:** Create public CSS/JS, public components, marketing images, and
`tests/Feature/Http/PublicWebsiteTest.php`. Modify layout, welcome, cookie banner,
Vite configuration and changelog. Do not change panel providers, shared `app.css`,
shared `app.js`, or existing compact `partials.footer`.

**Consumes:** Existing auth route names, `Version` facade, fixed channel list and
generated hero path from the spec. Existing public views require panel/card/btn/
muted/chip/flash tokens temporarily until later tasks modernize their markup.
**Produces:** The layout interface above; responsive public buttons, navigation,
footer and typography; hero delivery assets; `public.css` and `public.js` Vite inputs.

- [ ] Create PHPUnit test with `php artisan make:test --phpunit Http/PublicWebsiteTest --no-interaction`.
  Use real HTTP requests, disable Vite in semantic tests where a manifest is irrelevant.
  Removing navigation, required names or metadata must cause failure:

```php
$response = $this->get('/');
$response->assertOk()->assertSee('Clips hochladen')
    ->assertSee(route('filament.standard.auth.register'), false)
    ->assertSee(route('filament.standard.auth.login'), false)
    ->assertSee('RLP Dashcam')->assertSee('Dashcam Stories')
    ->assertDontSee('DashboardHeroes')->assertDontSee('Dashboard Heroes');
$document = new DOMDocument();
@$document->loadHTML($response->getContent());
$this->assertSame(1, $document->getElementsByTagName('h1')->length);
```

  Add DOM assertions for every local homepage anchor resolving, exactly one
  description/canonical/og:image, stable social URL across requests, no blog/gallery
  link, accessible theme/nav controls, eager dimensioned hero, and no shared app.js
  on marketing pages. Reuse existing cookie tests and add meaningful privacy-link
  and non-production notice checks. Check all six curated names and no contact emails.
- [ ] Run `docker compose exec sharing php artisan test --parallel --compact --filter=PublicWebsiteTest`;
  record expected missing-navigation/metadata failures before code changes.
- [ ] Generate responsive WebP/JPEG assets with installed GD/ffmpeg tools, preserving
  approved source image and logo identity. No new imaging dependency. Put the
  generated hero source in project assets or record reproducible provenance in spec.
- [ ] Add Vite entries without changing existing inputs. Use supported Tailwind syntax:

```css
@import 'tailwindcss';
@source '../views/**/*.blade.php';
@source '../js/public.js';
@custom-variant dark (&:where(.dark, .dark *));
@theme { --color-brand: #f97316; --color-ink: #0c1924; }
```

  Use real accessible orange CTA contrast, standard utilities and theme tokens,
  not global `.grid`/`.container` redefinitions. Keep content styles scoped.
- [ ] Build the Blade components and layout. Preserve sections/stacks and compact
  legacy class compatibility while introducing the new public frame. Use a native
  mobile disclosure and native FAQ details/summary. Theme storage failures must not
  break navigation/cookie actions. Initialize stored theme before paint if possible.
- [ ] Implement the complete homepage sections from the spec, dark photographic
  hero and light surfaces, substantial responsive spacing and system fonts. Footer
  uses the six exact names with a shared decorative Heroicon; no invented URLs.
  Help content explains actual uploads/distribution/offers, not legal guarantees.
- [ ] Implement metadata defaults using section values, stable image URLs and
  noindex handling for sensitive/error pages. Do not include token query values.
- [ ] Run focused HTTP tests plus `CookieBannerTest|ApiDocsPageTest`, build with
  `docker compose exec sharing npm run build`, and format PHP with Pint.
- [ ] Self-review diff, update changelog with implemented behavior, commit and push
  as `feat(frontend): redesign public homepage and navigation`. Report interfaces,
  asset sizes and exact test/build evidence. Controller dispatches task review.

### Task 2: Informational documents, error pages and game

**Files:** Supporting pages, document view/controller/service, public CSS if needed,
game assets, routes/web.php, Vite config, `tests/Feature/Http/PublicDocumentTest.php`.
**Consumes:** Task 1 layout sections, button component, scoped editorial typography.
**Produces:** Styled existing public URLs, individual metadata, functional unchanged game.

- [ ] Create the PHPUnit test via Artisan. Test actual HTTP responses:

```php
foreach (['/changelog', '/license', '/impressum', '/datenschutz', '/tos', '/api-docs', '/game'] as $path) {
    $response = $this->get($path);
    $response->assertOk()->assertSee('<main', false)->assertSee('name="description"', false);
}
$this->get('/unknown-public-page')->assertNotFound()->assertSee('noindex');
```

  Assert one h1, per-page titles/canonicals, real license/changelog text, existing
  API links, game canvas/buttons and no external font request. Verify no duplicate
  h1 from stored imprint content. No cosmetic class assertions.
- [ ] Run filtered test to record failures before implementation.
- [ ] Keep paths and route names. Add thin controller and fixed-path document
  service via Artisan class generators. Service returns title/content data for
  changelog/license only; markdown disallows unsafe raw HTML/links. License text is
  escaped with preserved whitespace. No arbitrary file or slug-based file access.

```php
Route::get('/changelog', [PublicDocumentController::class, 'changelog'])->name('changelog');
Route::get('/license', [PublicDocumentController::class, 'license'])->name('license');
```

- [ ] Modernize information-page spacing, headings and metadata while preserving
  legal substance, existing Page content and dynamic API sets. Use scoped editorial
  typography rather than duplicating utilities on every prose descendant.
- [ ] Replace robot-heavy inline error presentation with useful compact recovery
  actions; preserve exception diagnostic visibility only in debug mode and correct
  HTTP status codes. Do not expose sensitive URL values through metadata.
- [ ] Move the existing game logic into a page-only Vite entry; keep scoring,
  controls and restart behavior. Use system fonts, responsive canvas and clear
  keyboard instructions. Remove global body/button overrides and external fonts.
  Canvas must not prevent page scrolling when not actively controlled.
- [ ] Run focused tests and ApiDocsPageTest; build; format PHP. Update changelog as
  needed, self-review, commit and push `feat(frontend): unify public information pages`.

### Task 3: Offers and token pages without regressions

**Files:** Offer, token, channel confirmation and video/token component views,
`resources/js/offers.js`, Vite input, and PublicTransactionalPagesTest.
Modify existing download modules only if a demonstrated integration defect needs it.
**Consumes:** Public frame, `page_assets` section, existing signed routes,
`ZipDownloader`/`DownloadModal` contracts and relevant existing controller tests.
**Produces:** Accessible responsive transactional pages and page-specific download entry.

- [ ] Read existing OfferControllerTest and TokenApprovalControllerTest setup.
  Create real factory-backed valid signed requests to verify one h1, actual POST
  destinations/CSRF, selection controls and noindex without token-bearing metadata.

```php
$this->get('/offer/1/1')->assertForbidden();
// In a real factory-backed signed response, assert canonical and og:url are absent:
$xpath = new DOMXPath($document);
$this->assertSame(0, $xpath->query('//link[@rel="canonical"]')->length);
$this->assertSame(0, $xpath->query('//meta[@property="og:url"]')->length);
```

  Use existing valid fixtures for route-model binding; do not accept a 404 as the
  signature test. Keep new PHP test helpers concrete and obey ADR 0001.
- [ ] Run focused test to establish failure for missing h1 or incorrect form semantics.
- [ ] Replace inline layout/formatting with public utilities/components. Keep form
  IDs, field names, signed action URLs, selection/download/return actions and error
  states. Put native video controls outside checkbox labels. Dimension previews;
  no autoplay. Preserve clear disabled/already-downloaded state.
- [ ] Load `offers.js` only from offer show pages via `page_assets`. Match existing
  downloader constructor contract after reading its implementation. Import its
  dependencies only there, avoiding a second initialization and keeping panel app.js
  untouched. Use native disabled controls and accessible progress/status output.
- [ ] Modernize token panels and channel confirmation pages using existing route
  responses, one h1 and real form actions. Remove duplicate footer/content notices
  only where the common layout already renders the same information.
- [ ] Run new and existing offer/token/ZIP tests, build and Pint. Self-review,
  update changelog, commit and push `feat(frontend): refresh public offer and confirmation pages`.

### Task 4: Whole-branch verification and review

**Files:** Review reports in the plan workspace; plan progress and spec asset provenance.
**Consumes:** Completed tasks, test evidence and built assets.
**Produces:** Verified branch, review fixes, final report and draft PR if available.

- [ ] Use `superpowers:verification-before-completion` and `requesting-code-review`.
- [ ] Run `docker compose exec sharing php artisan test --parallel --compact` and
  `docker compose exec sharing npm run build`. Run relevant installed quality checks.
- [ ] Inspect generated manifest and public CSS isolation; compare route snapshots,
  allowing only action-class changes for unchanged changelog/license URLs.
- [ ] Resolve browser tooling without installing a project dependency. Use an
  existing external browser endpoint if available. Record explicit limitation if
  no browser can run under the environment's constraints; do not fabricate results.
- [ ] Exercise 360, 390, 768, 1024, 1440 and 1920 pixel widths plus an intermediate
  width, light/dark, no-JS navigation, keyboard focus, cookie notice, links, assets,
  console errors, image sizes and visible layout shifts. Inspect screenshots.
- [ ] Smoke-check internal panel auth presentation and signed offer interactions.
- [ ] Dispatch full-branch review with diff package, reports and spec. Fix confirmed
  defects through the implementer and scoped re-review, rerunning covering checks.
- [ ] Record completed tasks, actual evidence and limitations; commit/push final
  records. Use finishing-a-development-branch without merging into development.

## Plan review and execution decision

Spec coverage: tasks 1-3 cover homepage, public supporting pages, signed flows,
assets, accessibility, SEO and responsive behavior; task 4 covers verification.
Shared interfaces are the layout sections, button component and Vite entries.
Task 2/3 preserve Task 1 entries when extending Vite. The public stylesheet's
legacy compatibility prevents intermediate page regressions without touching panels.
The user's instruction to implement the approved design authorizes execution of
this plan. Work stays on their existing feature branch and mounted Docker checkout.
