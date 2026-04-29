# 0006. Changelog Maintenance And Unreleased Entry Policy

## Status

Accepted

## Context

This project already maintains a human-readable `CHANGELOG.md` and states that its format is based
on Keep a Changelog.

Keep a Changelog defines a changelog as a curated list of notable changes and recommends an
`Unreleased` section that is continuously maintained until the next version is released.

Without an explicit project rule, changelog maintenance tends to drift:

- code changes are merged without corresponding release notes
- noteworthy architectural and testing changes are only visible in commits or pull requests
- releases require reconstructing change history after the fact
- the `Unreleased` section stops being a reliable source of truth for upcoming releases
- developer-workflow and documentation decisions disappear even though they affect maintainability

This project already uses a concrete `Unreleased` structure in `CHANGELOG.md`:

- Keep a Changelog categories such as `Added`, `Changed`, `Fixed`, `Removed`, and `Security`
- grouped topical bullets like `**Laravel/Boost**`, `**Video Ingest Pipeline**`, or
  `**Refactored ZIP job payload handling**`
- concise, human-oriented summaries of notable application changes
- release sections that are useful to maintainers without requiring them to read commit history

That current structure is the house style for ongoing changelog maintenance.

## Decision

All notable project changes must be recorded in `CHANGELOG.md` under `## [Unreleased]` as part of
the same work in which the change is introduced.

The changelog policy is:

- follow Keep a Changelog as the governing format reference
- maintain the `Unreleased` section continuously instead of reconstructing it later
- record notable code, architecture, testing, documentation, configuration, dependency, and
  developer-workflow changes
- write entries for humans, not as raw commit-log fragments
- keep using the project's established `Unreleased` style unless a dedicated follow-up decision
  changes it

### Required format rules

- entries belong under existing Keep a Changelog categories such as `Added`, `Changed`, `Fixed`,
  `Removed`, `Deprecated`, and `Security` when appropriate
- within a category, changes should be grouped under a short topical label in the current house
  style, for example `- **Video Ingest Pipeline**`, `- **documentation**`, or `- **adr**`
- each grouped entry should summarize the externally, operationally, or maintainability-relevant
  change in concise prose
- entries should describe the actual outcome, not the implementation process
- entries should avoid duplicating commit messages, pull request titles, or low-level file lists
  unless the file name itself is important to users or maintainers

### Operational rule

Any change that is significant enough to be merged is presumed significant enough to be evaluated for
changelog impact.

If a change is notable, the author must update `CHANGELOG.md` in the same branch or change set.

Examples of normally notable changes in this project:

- new application features or user-visible behavior
- behavior changes in video upload, ingest, preview generation, assignment distribution, downloads,
  channel access, notifications, or ZIP creation
- new extension points, contracts, services, use cases, or runtime boundaries
- authentication or authorization changes
- queue, Horizon, scheduler, mail, storage, Dropbox, Reverb, or filesystem changes
- public configuration changes
- dependency upgrades that affect runtime, deployment, or development
- test architecture, test tooling, or developer-workflow changes
- class renames that affect public usage, discovery, documentation, or maintenance conventions
- new ADRs or documentation that materially changes project guidance

Purely internal edits with no meaningful user, maintainer, release, or operational impact may be
omitted, but that should be the exception, not the default.

## Rules

1. **Evaluate every mergeable change**

   Every change set must be checked for changelog impact before it is considered complete.

2. **Update `Unreleased` immediately**

   Notable changes must be added under `## [Unreleased]` in the same branch or change set that
   introduces them.

3. **Use the existing category structure**

   Entries must use Keep a Changelog categories. Add a missing category only when the change belongs
   there.

4. **Group related entries**

   Related bullets should be grouped under a topical label. The label should describe the area of the
   project, not the implementation mechanism.

5. **Write for release readers**

   Changelog entries should explain what changed and why it matters. They should not read like raw
   commit messages.

6. **Do not document noise**

   Formatting-only edits, tiny internal cleanups, and changes with no meaningful release or
   maintainer impact may be omitted.

7. **Release preparation moves entries, it does not rediscover them**

   Preparing a release should primarily move curated `Unreleased` entries into a dated version
   section. It should not require reconstructing notable changes from Git history.

## Consequences

Positive consequences:

- `CHANGELOG.md` remains the primary source of truth for upcoming releases
- releases are easier to prepare because notable changes are curated continuously
- maintainers and users can understand project evolution without reading commit history
- architectural, testing, documentation, and developer-experience changes remain visible alongside
  feature work
- changelog review becomes part of normal change review instead of a separate release-time task

Trade-offs:

- every meaningful change now carries a documentation obligation
- authors must decide whether a change is notable before merging
- duplicate thinking is required when the code change is already described elsewhere, for example in
  an ADR or pull request
- overly verbose entries can reduce changelog usefulness if they are not curated

Rejected alternatives:

- **Update the changelog only at release time**: rejected because it leads to omissions, weakens
  release quality, and turns changelog writing into archaeology.
- **Use commit history or pull requests as the release log**: rejected because those records are too
  implementation-focused and are not curated for release readers.
- **Only document user-facing features**: rejected because architecture, testing, configuration, and
  developer-workflow changes materially affect maintainability and release risk in this project.
