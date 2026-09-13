# Editorial blog

Date: 2026-09-13
Branch: `feature/327-blog`
Ticket: #327
Status: design approved in conversation; written specification awaiting review.

## Objective and boundaries

Add an editorial blog to the public DashClip Delivery website: articles with a category, tags,
media and search-engine metadata, maintained in the administration panel and delivered by the
existing public frontend.

The blog was deliberately excluded from the frontend redesign
(`docs/superpowers/specs/2026-09-12-public-frontend-redesign-design.md:19`). The navigation slot was
reserved, but no routes, models, management screens or cards exist. This project supplies them.

Articles exist in German and English. Each language has its own slug and its own indexable address.

Out of scope, each a later ticket of its own: the newsletter subscription shown in the overview
mockup sidebar, and the social profile links shown in the article mockup footer. The per-article
share bar is in scope; it is a set of plain links and loads no third-party script.

## Verified baseline

- PHP 8.4.21, Laravel 13.26.1, Filament 5.7.6, Livewire 4.4.1, Passport 13.7.6, Shield 4.3.1.
- MariaDB 10.11.18 in development and staging. Tests run on SQLite in memory (`phpunit.xml:49`).
- No translation package is installed. The translation mechanism below is built in the application.
- `internachi/modular` is required but unused; there is no `app-modules/` directory. The blog
  follows the existing structure under `app/Models`, `app/Repository`, `app/Application`,
  `app/Http/Controllers` and `app/Filament/Admin`.
- The public locale is resolved from a cookie in `app/Http/Middleware/SetPublicLocale.php`. The URL
  carries no language segment today.
- Public pages are server-rendered Blade over `resources/views/layouts/app.blade.php` with the
  `x-public.*` components introduced by the redesign.
- Authorization uses `bezhansalleh/filament-shield`.
- No `FULLTEXT` index exists anywhere in `database/migrations`.

Mockups: `tmp/mockup_blog.png` (overview), `tmp/mockup_blog_entry.png` (article).

## Binding guidance

The administration panel is the editorial and administrative surface only. The public blog frontend
has no technical relationship to it.

The data flow is `admin panel -> model and database -> public frontend`, and expressly not the
reverse.

The following are not permitted on public blog pages:

- panel components, panel layouts, panel CSS or panel JavaScript,
- panel user-interface conventions in the public design,
- any coupling of public routing to the panel.

Public blog pages are part of the same website as the homepage and reuse the existing public
building blocks: header, navigation, footer, `x-public.button`, `x-public.section-heading`,
`x-public.metadata`, typography, container, spacing, breakpoints, the design tokens `bg-panel`,
`text-muted`, `border-border` and `bg-ink`, and the consent infrastructure.

The panel receives its own form and table classes for administration only. They are never reused
publicly, and public components are never reused in the panel.

The accepted architecture decisions apply without exception. ADR 0002 fixes the class suffixes,
ADR 0003 the SOLID expectations and patterns, ADR 0005 the method-level PHPDoc with imported
type references, and ADR 0001 the test layering without mocks. Per ADR 0004 the area defines a
`BlogException` base with specific descendants such as `PostNotPublishedException` and
`TranslationMissingException` rather than throwing generic exceptions. Commits follow ADR 0007
and reference #327; the changelog entry follows ADR 0006.

## Data model

Language-neutral facts live on the article. Everything a reader sees in one language lives on the
translation. This keeps `hreflang` pairs explicit and lets German go live while English is still a
draft, instead of publishing an empty English page.

All tables carry a `blog_` prefix so the area is unambiguous and cannot collide with existing or
future generic tables.

`blog_posts`

- `id`, `author_id` referencing `users`, `category_id` referencing `blog_categories`
- `image_path` nullable, the shared article image
- `created_at`, `updated_at`
- index on `category_id`, index on `author_id`

`blog_post_translations`

- `id`, `post_id`, `locale`
- `slug`, `title`, `excerpt`, `content`
- `meta_title`, `meta_description`, `canonical_url`, `is_indexable`
- `status` and `published_at`, both per language
- `reading_minutes`, derived on save
- unique `(post_id, locale)` and unique `(locale, slug)`
- composite index `(locale, status, published_at)` serving every public listing

`blog_categories` and `blog_category_translations`

- The category carries `slug` and `icon`; name and description are translated, with unique
  `(category_id, locale)` and unique `(locale, slug)`.

`blog_tags`, `blog_tag_translations` and `blog_post_tag`

- Tags mirror the category shape and join many-to-many to `blog_posts`.

A `PostStatusEnum` supplies `DRAFT`, `SCHEDULED`, `PUBLISHED` and `RETRACTED`, named per ADR 0002.

The author is read through the relation and rendered with the existing `display_name` accessor
(`app/Models/User.php:205`, `submitted_name ?? name`). Unlike `Clip`, which copies the name at
upload time (`app/Models/Clip.php:64`), the blog does not snapshot it, so a later name change is
reflected in older articles.

Categories are maintained editorially in the panel and attached to an article afterwards. An
article has exactly one category, which the breadcrumb and the card badge in both mockups assume,
and any number of tags.

## Public routing and localization

The language prefix applies to the blog only:

- `/blog`, `/blog/{slug}`, `/blog/kategorie/{slug}`, `/blog/thema/{slug}`
- `/en/blog`, `/en/blog/{slug}`, `/en/blog/category/{slug}`, `/en/blog/tag/{slug}`

German carries no prefix, matching every existing public address. All other public routes keep
their current form and their cookie-driven language. No redirect is introduced and no existing
address changes, so inbound links and indexing are untouched.

A route group sets the application locale from the prefix and overrides the cookie for the duration
of the request. `SetPublicLocale` keeps its current behavior everywhere else. The language switch
on a blog page targets the sibling translation when one exists and falls back to the blog overview
in the chosen language when it does not, rather than producing a dead address.

Slug lookup is scoped by locale, so the same slug may exist once per language.

## Editorial backend

A post resource in the admin panel provides create, edit, preview and duplicate, with a translation
form per language and a visible marker for languages that are still missing. Draft, scheduled
publication, publication and retraction are driven by `PostStatusEnum` and `published_at` per
language.

Category and tag resources cover their own maintenance, including the icon and description the
overview sidebar renders.

Article image and in-content images upload through the existing storage configuration. Metadata
fields cover meta title, meta description, social image, canonical and indexability.

Every screen is guarded by policies registered through Shield.

## Public components and pages

New components live under `resources/views/components/public/blog/`:

`card`, `meta`, `category-badge`, `hero`, `article-content`, `related-articles`, `pagination`,
`share`.

`article-content` renders the structured body including the two callout variants shown in the
article mockup, a hint and a warning. `share` offers copy link, X, Facebook, LinkedIn, WhatsApp and
mail as plain anchors.

Pages: overview with hero, filter chips, article grid, popular-topics sidebar and pagination;
category page; tag page; article page with breadcrumb, body, share bar, sidebar and related
articles; search results. Each has an explicit empty state.

The homepage gains an "Aus dem Blog" section that renders the same `card` component as the
overview. No second card exists for the homepage. The navigation activates the blog entry and gains
the search affordance shown in the mockup.

## Querying, caching and performance

`App\Repository\PostRepository` encapsulates data access. `App\Application\Blog` holds
`ListPublishedPostsUseCase`, `ShowPostUseCase` and `SearchPostsUseCase`, matching the existing
`app/Application` domains and the suffixes required by ADR 0002 and the responsibilities of
ADR 0003.

Every public query filters to the active locale, `PostStatusEnum::PUBLISHED`, and `published_at` in
the past, and is served by the `(locale, status, published_at)` index. Category, tags, author and
image are eager loaded.

The homepage block, the popular-topics list and the category counts are cached and invalidated when
an article is published or retracted, through an observer on the translation.

A test asserts the query count for the homepage and the overview so that an N+1 regression fails
the suite rather than reaching production.

## Search

The search covers title, excerpt and content of the active language.

It is implemented with `LIKE` predicates behind `PostRepository`, not with a `FULLTEXT` index. The
reason is that the suite runs on SQLite in memory while production runs MariaDB, so `MATCH ...
AGAINST` could not be exercised by any test, and the project has no precedent for a fulltext index.
A driver-dependent search path would put the production code path outside test coverage, which is
the failure mode already recorded for SQLite-only testing in this project.

`LIKE` is adequate at the expected article count. If the corpus outgrows it, the replacement is a
fulltext index or a search service behind the same repository method, together with a test database
that matches production. That migration is a separate decision and is not taken here.

Titles are ranked above excerpt and content matches so the ordering is predictable.

## Search-engine metadata

`x-public.metadata` is extended with article metadata. Its hardcoded indexable path list
(`resources/views/components/public/metadata.blade.php:3`) is decoupled, because it silently marks
every unlisted page as `noindex` and would do so for every article.

Each article emits reciprocal `hreflang` for its published siblings plus `x-default`, structured
data of type `Article`, and a canonical address. The sitemap gains articles, categories and tags.
An RSS feed is published per language.

Retracted, scheduled and draft translations are absent from the sitemap, the feed and every public
listing.

## Verification and acceptance

Use `superpowers:verification-before-completion` before reporting completion. All commands run in
the sharing container with `docker exec dashclip-delivery-sharing-1 php artisan test --parallel`.
Do not add quality dependencies without authorization.

Required automated checks:

- Visibility: draft, scheduled and retracted translations never appear publicly, in the sitemap or
  in the feed.
- Localization: prefixed routes set the locale, the language switch targets the sibling translation
  and falls back correctly when none exists, and slug lookup is locale-scoped.
- Uniqueness: the same slug is accepted once per language and rejected twice within one language.
- Query counts for the homepage block and the overview, proving the absence of N+1.
- Public blog pages contain no panel assets in their markup.
- Editorial screens reject unauthorized users.
- `PublicWebsiteTest.php:75` is updated; it currently asserts that no `/blog` link exists anywhere.
- The full suite, a production Vite build, and Pint on touched PHP files.

Required browser checks: phone, tablet, laptop, desktop and intermediate widths with text zoom;
keyboard navigation and focus visibility; both themes; heading structure and contrast; no
horizontal overflow; article images carry dimensions and do not shift layout.

The final report names changed areas, new and reused components, the checks actually executed, and
any remaining limitation. No check is described as passed without execution.

## Workflow handoff

After review of this specification, use `superpowers:writing-plans` to produce the implementation
plan. Implementation starts only after that plan is agreed.
