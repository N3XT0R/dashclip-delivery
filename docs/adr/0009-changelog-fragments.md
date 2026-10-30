# 0009. Changelog Entries Are Written As Fragments, CHANGELOG.md Is Generated

## Status

Accepted. Amends ADR 0006 in how entries are recorded; the content rules of ADR 0006 stay in force.

## Context

ADR 0006 requires every notable change to be recorded under `## [Unreleased]` in the same branch.
With several feature branches open at once, all of them edit the same few lines at the top of
`CHANGELOG.md`. Every merge between `4.x`, `4.x-dev` and long-running feature branches then
conflicts in the changelog, and each conflict has to be resolved by hand without losing or
duplicating an entry.

ADR 0006 leaves room for this: the established `Unreleased` style is kept "unless a dedicated
follow-up decision changes it". This is that decision.

## Decision

Changelog entries are stored as fragments below `.changes/` and `CHANGELOG.md` is generated from
them with the `markwalet/laravel-changelog` development tooling.

- each version is a folder: `.changes/unreleased/` for the next release, `.changes/<version>/`
  for released ones; a released folder carries its date in a `release-date` file (`Y-m-d`)
- a branch writes its entries into its own fragment, `.changes/unreleased/<branch>.xml`, so
  branches never touch the same file
- `CHANGELOG.md` is built only by the release pipeline when a version is tagged; it is never
  edited, regenerated or committed by hand

### Workflow

```shell
# record an entry for the current branch (type is one of the six categories)
php artisan changelog:add --type=fixed --message="**Offer downloads**: A closed dialog stays closed."

# preview without writing CHANGELOG.md, then commit the fragment with the change
php artisan changelog:current
git add .changes/unreleased/<branch>.xml
```

The project skill `changelog` (`.claude/skills/changelog/`) walks through these steps.

Releasing is done by `.github/workflows/changelog.yml`. Pushing a version tag on a release branch
(e.g. `4.13.0` on `4.x`) runs `changelog:release <tag>` and `changelog:generate` and commits the
dated version folder and the regenerated `CHANGELOG.md` to that release branch. Between two tags
`CHANGELOG.md` therefore shows the last released state, and the upcoming changes live in
`.changes/unreleased/`.

A fragment may also be written or edited directly. A message that needs several lines, for example
a label with a nested list, is written with its line breaks and continuation indentation in the
XML; it is then rendered exactly as written. A single-line message is wrapped at 100 characters.

### House-style adjustments to the tooling

The package defaults do not match ADR 0006, so the application replaces three parts of it:

- `KeepAChangelogFormatter` renders the sections in the canonical order `Added`, `Changed`,
  `Deprecated`, `Removed`, `Fixed`, `Security`, adds the release date to released versions and
  refuses any other type, so generating fails instead of inventing a section
- `DatedXmlReleaseAdapter` orders versions semantically (`4.12.0` above `4.9.0`), keeps the
  entries in the order they were written instead of sorting them alphabetically, and writes the
  release date on `changelog:release`
- `EscapedXmlFeatureAdapter` stores messages as escaped text, so `&` and `<` in a message do not
  corrupt the fragment

The page header lives in the published template `resources/views/vendor/changelog/changelog.blade.php`.

## Rules

1. **Fragments are the source of truth**

   An entry exists when its fragment exists. Edits to `CHANGELOG.md` that are not the output of
   `changelog:generate` are overwritten and therefore a defect.

2. **One fragment per branch**

   Entries are added to the fragment of the branch that introduces the change. Merging branches
   merges folders of files, not lines of one file.

3. **Content rules of ADR 0006 apply unchanged**

   English, the six categories, a topical bold label, prose for release readers, no noise. The
   message of a fragment is held to the same standard as a hand-written entry was.

4. **`CHANGELOG.md` is built on a tag only**

   Only the changelog pipeline writes `CHANGELOG.md` and moves fragments into a version. A change
   set never contains `CHANGELOG.md` or a `changelog:release`.

5. **Commits are checked before they are created**

   The project hook `.claude/hooks/check-commit-conditions.sh`, registered for everyone in
   `.claude/settings.json`, blocks a commit made through Claude Code when `CHANGELOG.md` is part
   of it, when a notable change has no fragment for its branch, when a fragment uses another type,
   or when staged PHP files fail Pint. Release lines (`4.x`, `4.x-dev`) and merge commits are
   exempt from the fragment check, because their entries arrive with the merged branches.

## Consequences

Positive consequences:

- changelog merge conflicts between branches disappear
- release preparation needs no manual changelog step: tagging builds it
- category order, dates and line wrapping are enforced by the generator instead of by review

Trade-offs:

- `CHANGELOG.md` lags behind the unreleased work until the next tag; the upcoming changes are read
  from `.changes/unreleased/` (or `php artisan changelog:unreleased`)
- the full history was migrated into fragments once; the generated file normalises a few blank
  lines and trailing spaces compared with the hand-written version
- the house-style classes depend on the internals of a third-party package and must be checked
  when it is upgraded

Rejected alternatives:

- **Keep editing `CHANGELOG.md` directly**: rejected because of the recurring merge conflicts.
- **Freeze the old history in a separate file and only generate new versions**: rejected in favour
  of one consistent source for every version.
- **A non-PHP fragment tool**: rejected because the development tooling of this project runs
  inside the PHP container and Artisan is the established entry point.
