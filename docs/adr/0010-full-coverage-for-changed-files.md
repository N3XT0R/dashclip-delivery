# 0010. Changed Files Leave With Full Line Coverage

## Status

Accepted

## Context

ADR 0001 defines how tests are written and layered, but not how much of the code they must reach.
The nightly coverage run measures the whole suite and reports it to Qlty, so the overall number is
known, yet nothing ties it to the work that is being done.

A global coverage target does not move the number in a useful direction: it is either already met
and changes nothing, or it is far away and blocks unrelated work. Gaps also tend to sit in exactly
the files that are changed most, because those are the files that grew fastest.

The Boy Scout Rule ("leave the campground cleaner than you found it") gives a target that grows with
the work instead: whoever changes a file brings it to full coverage. Files nobody touches stay as
they are, files that are worked on regularly reach full coverage first, and the overall coverage
rises with every pull request without a dedicated clean-up project.

## Decision

Every PHP file in the coverage source that a branch adds or modifies has 100% line coverage when the
branch is merged. The whole file counts, not only the changed lines.

### Scope

- The coverage source is the `<source>` section of `phpunit.xml` (currently `app/`, minus the files
  and directories excluded there). Files outside it, such as tests, migrations, config, views and
  language files, are not measured.
- A file counts as changed when the branch diff against its target branch (`4.x-dev` or the release
  branch) adds or modifies it. Deleted files and pure renames without content changes do not count.
- Mechanical changes across many files that do not change behavior, for example a repository-wide
  Pint run, an automated rename or a dependency upgrade that only adjusts signatures, are exempt.
  They are kept in their own commits so the exemption stays visible in review.

### Coverage

- Line coverage is measured with the regular test suite: `php artisan test --coverage-clover`, with
  Xdebug or PCOV as the driver. A file must not depend on a test that only runs locally or manually.
- The coverage comes from tests that follow ADR 0001: real code paths, correct layer, no mocks or
  stubs. A test that only executes lines without asserting their behavior does not satisfy this ADR.
- If the existing tests of a changed file do not reach full coverage, the missing tests are added in
  the same branch, even when the gap is in code the branch did not touch.

### Exceptions

Lines that cannot run inside the PHPUnit process, for example a call that ends the process with
`exit` (see the `SabreServerRunner` case in ADR 0001), may be excluded with `@codeCoverageIgnore`.

Rules:

- the exclusion covers the smallest possible region: a single line or method, never a whole class
- the PHPDoc of the excluded element states why it cannot be tested
- a whole file or directory is only excluded through `phpunit.xml`, with the reason given in the
  pull request that adds the exclusion
- an exclusion is never used to skip code that is merely tedious to test

### Verification

Locally, the coverage of the changed files is checked with:

```shell
docker compose exec sharing php artisan test --parallel --coverage-clover=coverage/clover.xml
```

and the files of `git diff --name-only --diff-filter=AM 4.x-dev...HEAD -- app/` are compared with
the line counts in `coverage/clover.xml` (or the HTML report via `--coverage-html`).

Reviewers check the coverage of the changed files before approving. An automated pull request check
that reads the clover report and fails for a changed file below 100% is the intended enforcement
and replaces the manual comparison once it exists.

## Consequences

Positive consequences:

- overall coverage rises with every pull request without a separate clean-up effort
- the most frequently changed files, which carry the most risk, reach full coverage first
- the requirement is local to the work: a pull request is never blocked by files it did not touch
- untested legacy code is covered at the moment someone needs to understand it anyway

Trade-offs:

- a small change to a large, poorly tested file can require considerably more test work than the
  change itself; this cost is accepted, and such files may be split while they are covered
- line coverage does not prove that behavior is verified; the ADR 0001 rules on meaningful tests
  remain the safeguard against tests that only execute lines
- running the suite with a coverage driver is slower than a plain run

Rejected alternatives:

- **A global coverage threshold**: rejected because it either changes nothing or blocks unrelated
  work, and it does not point at the files that matter.
- **Coverage of changed lines only**: rejected because it leaves untested code next to the change
  in place, so the overall coverage barely moves and files never become fully covered.
- **A lower percentage per changed file (e.g. 80%)**: rejected because the remaining gap is always
  the hard-to-test part and would stay untested permanently.
