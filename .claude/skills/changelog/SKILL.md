---
name: changelog
description: Use when a change is notable enough for the changelog, before committing code, configuration, dependency, workflow or ADR changes, when the commit hook reports a missing or invalid changelog fragment, or when the user asks for a changelog entry (e.g. /changelog).
---

# Changelog fragments

## Overview

Changelog entries are fragments below `.changes/`, one XML file per branch. `CHANGELOG.md` is
generated from them by the release pipeline when a version is tagged and is never edited or
committed by hand. ADR 0006 sets what an entry says, ADR 0009 how it is recorded.

## Steps

1. **Find the fragment** of the current branch: `.changes/unreleased/<branch>.xml`, e.g.
   `.changes/unreleased/feature/changelog-fragments.xml`. Add to it if it exists.
2. **Pick the type**, exactly one of `added`, `changed`, `deprecated`, `removed`, `fixed`,
   `security`. Anything else fails the build of the changelog.
3. **Write the message** (rules below), then record it:

   ```shell
   docker compose exec sharing php artisan changelog:add --type=fixed \
     --message="**Offer downloads**: A closed download dialog stays closed when the page is reloaded."
   ```

   A single-line message is wrapped at 100 characters on generation. For a label with a nested
   list, edit the XML directly and write the line breaks and indentation as they should appear:

   ```xml
   <change type="security">**Packages**
       - upgraded packages to newest version (e.g. laravel or npm dependencies)</change>
   ```

   Escape `&` as `&amp;` and `<` as `&lt;` when editing by hand; `changelog:add` does it for you.
4. **Preview** the result: `docker compose exec sharing php artisan changelog:current` for the
   branch, `changelog:generate --dry-run` for the whole file. Do not write `CHANGELOG.md`.
5. **Stage the fragment** together with the change: `git add .changes/unreleased/<branch>.xml`.

## Message rules (ADR 0006)

| Rule | Detail |
|---|---|
| Language | English, whatever language the conversation or spec uses. |
| Shape | `**Topical label**: prose`. The label names the area (`**Offer downloads**`), not the mechanism. |
| Content | What changed and why it matters to a release reader. The outcome, not the implementation. |
| Wording | No framework or library names and no internal structure (panels, guards, class names). No em dashes. |
| Noise | Formatting-only edits and tiny internal cleanups need no entry. |

## Release

Releasing is the pipeline's job: tagging a version on the release branch moves the unreleased
fragments into `.changes/<version>/`, dates it and regenerates `CHANGELOG.md`. Never run
`changelog:release` or commit `CHANGELOG.md` by hand.

## Common mistakes

- Committing `CHANGELOG.md`: the commit hook blocks it.
- Writing into another branch's fragment: entries belong to the branch that introduces the change.
- Inventing a type such as `improved`: use `changed`.
