---
name: release-news
description: Use when a new DashClip Delivery version is being released or the user asks for release notes, a release announcement or a "what's new" blog post for a version (e.g. /release-news 4.10.0).
---

# Release news

## Overview

Every version gets a German and English news article that explains the release to submitters and
channel operators without technical knowledge. Deployments only run migrations, so a migration
publishes the article through `ReleaseNewsSeeder` and it is live right after the deploy.

Argument: the version (e.g. `4.10.0`). If missing, ask for it.

## Steps

1. **Branch** off the highest `*-dev` branch: `feature/release-news-<version>`. Land it via PR.
2. **Collect changes** from the `## [<version>]` section of `CHANGELOG.md`, or from
   `## [Unreleased]` if the version is not cut yet. Keep only what users notice. Drop refactorings,
   tests, tooling and internal fixes. Group into 3 to 5 topics, most useful first.
3. **Check real UI wording** in `lang/de/*.php` and `lang/en/*.php` before naming buttons, sections
   or pages in the text. Quote labels exactly.
4. **Write the content** in `database/seeders/data/releases/`:
   - `<version>.php`: `key` = `release-<version with dots as hyphens>`, `translations.de/en` with
     `slug` (`neu-in-version-4-10-0` / `new-in-version-4-10-0`), `title`
     (`Neu in Version 4.10.0: <highlight>` / `New in version 4.10.0: <highlight>`), `excerpt`,
     `meta_title` (`... | DashClip Delivery`), `meta_description` (about 150 to 160 characters).
   - `<version>.de.md` and `<version>.en.md`: an intro paragraph, one `##` section per topic
     (what changed, why it helps, how to use it, with numbered steps where useful), and a final
     `## Dein nächster Schritt` / `## Your next step` with links. 400 to 700 words.
   - Use `4.9.0.*` as the reference for tone and structure.
5. **Cover**: `node database/seeders/data/images/build-covers.mjs release-<x-y-z>`, then look at
   `covers/release-<x-y-z>.webp` and check that the title wraps cleanly. Pass the key, otherwise
   all covers are re-rendered.
6. **Migration** `database/migrations/<Y_m_d_His>_publish_release_news_<x_y_z>.php`:
   `up()` calls `app(ReleaseNewsSeeder::class)->run('<version>')`, `down()` stays empty. Copy
   `2026_09_18_200000_publish_release_news_4_9_0.php`.
7. **Verify**: run `tests/Integration/Seeders/ReleaseNewsSeederTest.php` (its completeness test
   covers every release file) and Pint on the new PHP files. Optionally seed locally and open
   `/blog/<de-slug>` and `/en/blog/<en-slug>`.
8. **Commit** `feat(blog): publish the <version> release news`, push, open the PR.

## Writing rules

| Rule | Detail |
|------|--------|
| Audience | Non-technical users. Explain the benefit, then how to use it. |
| Voice | German with "du", English with "you". Friendly, concrete, short sentences. |
| Forbidden terms | Framework or library names, "Panel", "Tenant", class names, ticket numbers, internal wording from the changelog. |
| Punctuation | No em-dashes. Use commas, colons or periods. |
| Callouts | Only `> [!NOTE]` and `> [!WARNING]`. Other types do not render. |
| Links | Relative app paths such as `/standard/profile`, `/standard/login`, `/standard/register`, `/blog`. |
| Accuracy | Describe behaviour as it is, including side effects users will notice. |

## Common mistakes

- Copying changelog bullets: they are written for developers. Rewrite them as user benefits.
- Calling the seeder from `DatabaseSeeder` instead of a migration: the article would not go live on deploy.
- Reusing a slug or key from an earlier release: the completeness test and the blog routes need unique values.
- Adding a CHANGELOG entry for the article: not needed, the release news mechanism is already documented.
