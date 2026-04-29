# 0007. Conventional Commits

## Status

Accepted

## Context

This project is a versioned Laravel video-sharing application with a maintained changelog, semantic
versioning, deployment documentation, developer workflow documentation, and a growing architecture
decision record.

Commit history is an important source of truth for what changed between releases, who introduced a
change, and whether a release requires a major, minor, or patch version bump.

Without a commit message convention, several problems appear over time:

- release notes must be assembled manually from free-form messages
- semantic versioning decisions depend on human recall rather than structured signals
- reviewers cannot quickly distinguish feature work, bug fixes, breaking changes, and maintenance
  from the commit list alone
- changelog entries cannot be cross-checked reliably against commit intent
- tooling for automated changelogs, release drafts, or version bumps cannot be applied cleanly

The Conventional Commits specification (v1.0.0) provides a lightweight, machine-readable, and
human-readable convention that maps directly onto Semantic Versioning and aligns with the way this
project already documents release-relevant changes.

## Decision

All commits to this repository must follow the Conventional Commits v1.0.0 specification.

The governing reference is: <https://www.conventionalcommits.org/en/v1.0.0/>

### Required format

```text
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

### Type

The `type` is mandatory and must be one of the following:

| Type       | When to use                                               | SemVer signal |
|------------|-----------------------------------------------------------|---------------|
| `feat`     | A new feature visible to users or operators               | MINOR         |
| `fix`      | A bug fix                                                 | PATCH         |
| `docs`     | Documentation changes only                                | none          |
| `style`    | Formatting, whitespace, code style; no behavior change    | none          |
| `refactor` | Code restructuring without adding features or fixing bugs | none          |
| `perf`     | Performance improvements                                  | PATCH         |
| `test`     | Adding or correcting tests                                | none          |
| `build`    | Build system, dependency, or tooling changes              | none          |
| `ci`       | CI configuration changes                                  | none          |
| `chore`    | Maintenance tasks that do not fit another type            | none          |
| `revert`   | Reverts a previous commit                                 | depends       |

### Scope

Scope is optional. When present, it must be a noun in parentheses describing the part of the
codebase or workflow the commit touches.

Examples:

- `feat(ingest)`
- `fix(storage)`
- `docs(adr)`
- `test(assignments)`
- `chore(deps)`
- `ci(actions)`

Recommended scopes for this project include:

- `adr`
- `assignments`
- `auth`
- `channels`
- `config`
- `docs`
- `filament`
- `horizon`
- `ingest`
- `mail`
- `notifications`
- `preview`
- `storage`
- `tests`
- `uploads`
- `zip`

### Description

The description is mandatory. It must immediately follow the `type[scope]:` prefix and be written as
a short imperative-mood summary. It should be lowercase unless a proper noun, class name, acronym, or
configuration key requires capitalization.

Rules:

- no trailing period
- describe the outcome, not the work session
- keep it concise enough to scan in `git log --oneline`

### Body

The body is optional and must be separated from the description by a blank line. Use it to explain
motivation, trade-offs, or additional context that does not fit in the description.

The body should answer "why" when that is not obvious from the commit itself.

### Footers

Footers are optional and must be separated from the body, or from the description when no body is
present, by a blank line.

Each footer token uses a hyphen-separated key, for example:

- `Reviewed-by`
- `Refs`
- `Closes`
- `Co-authored-by`

### Breaking changes

Breaking changes must be signaled in one of two ways, or both:

- append `!` after the type and optional scope: `feat(auth)!: remove legacy approval token flow`
- include a `BREAKING CHANGE:` footer with a description of what breaks and why

Both forms correlate with a MAJOR version bump in Semantic Versioning. They may be combined when the
description benefits from both the immediate `!` signal and the longer footer explanation.

### Examples

```text
feat(ingest): add retry detection for stale video steps
```

```text
fix(storage): preserve preview paths for soft-deleted videos
```

```text
docs(adr): add ADR 0007 for conventional commits
```

```text
test(assignments): cover expired assignment return flow
```

```text
chore(deps): update Laravel Horizon
```

```text
feat(auth)!: replace legacy channel approval tokens

BREAKING CHANGE: Channel approval links now use ActionTokenService. Existing
legacy approval URLs are no longer accepted.
```

## Rules

1. **Every commit uses the convention**

   All commits must start with a valid Conventional Commits type, optional scope, colon, space, and
   description.

2. **Use the most specific accurate type**

   `feat` and `fix` must be reserved for user-visible, operator-visible, or release-relevant behavior
   changes. Documentation-only ADR work uses `docs`, not `feat`.

3. **Scopes describe project areas**

   Scopes should describe areas such as `ingest`, `filament`, `storage`, `zip`, or `adr`. They should
   not describe temporary implementation details.

4. **Breaking changes are explicit**

   Any backwards-incompatible change must use `!`, a `BREAKING CHANGE:` footer, or both.

5. **Changelog and commit signals must align**

   Changelog entries required by ADR 0006 should be consistent with the commit types used in the
   related changes.

6. **Squash commits still follow the convention**

   When using squash merge, the final squash commit title must follow this ADR even if intermediate
   local commits are later discarded.

## Consequences

Positive consequences:

- commit history becomes scannable without opening individual diffs
- breaking changes are always explicitly flagged rather than discovered after release
- semantic version decisions can be derived more directly from the commit type list
- changelog entries can be cross-checked against commit signals
- tooling for automated release drafts or version bumps becomes applicable
- reviewers get a consistent signal about the intent and impact of each change

Trade-offs:

- authors must learn and apply the format consistently
- short or ambiguous commits require more thought about the correct type
- enforcing the convention in CI requires additional tooling if linting is added later
- some mixed commits may need to be split so that the type accurately reflects the change

Rejected alternatives:

- **Free-form commit messages**: rejected because they make release preparation, semantic
  versioning, and history navigation more costly.
- **GitHub squash-merge title convention only**: rejected because individual commits within a branch
  also carry review and bisect value and should be readable on their own.
- **Project-specific custom commit prefixes**: rejected because standard Conventional Commits already
  provide broad tooling support and shared expectations.
