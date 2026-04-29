# 0001. Test Architecture And Layering

## Status

Accepted

## Context

This is a Laravel Filament plugin package. Its test suite must verify real behavior across distinct
architectural layers without relying on PHPUnit mocks, stubs, or abstract fixtures.

The package ships with a `workbench/` directory — a self-contained Laravel application used as the
host environment during testing. It provides real models, providers, factories, routes, and Filament
panel configuration. Any concrete helper class needed exclusively for testing is built inside
`workbench/` as a real, production-style implementation rather than a test double.

The three layers of behavior the suite must cover are:

- **Feature** — real HTTP request flows through the package endpoint
- **Integration** — real collaboration with framework and runtime boundaries (filesystem, Gate,
  Eloquent, SabreDAV node behavior)
- **Unit** — isolated class behavior exercised without crossing runtime boundaries

## Decision

The test suite is organized by behavior layer. Every test exercises real code paths. PHPUnit mocks
and stubs are never used. Where a collaborator needs test-specific behavior, a concrete workbench
implementation is built instead.

### Feature tests

Tests in `tests/Feature/` must execute a real Laravel HTTP request against the package endpoint.

Rules:

- use real request dispatch through Laravel test helpers such as `$this->call()`, `$this->get()`,
  or JSON helpers where applicable
- use real package services for the request pipeline
- do not use PHPUnit mocks or stubs
- where the production runtime cannot be exercised inside the PHPUnit process (e.g. `SabreServerRunner`
  terminates via `exit`), replace only that final edge with a concrete workbench implementation while
  keeping the rest of the pipeline real

### Integration tests

Tests in `tests/Integration/` must exercise real collaboration with external or framework boundaries.

Examples in this package:

- Gate authorization
- real filesystem interaction through storage disks
- real Eloquent persistence
- configuration-driven service resolution

Rules:

- use real framework services, real filesystem disks, and real persisted records
- do not use PHPUnit mocks or stubs
- if a collaborator needs test-specific behavior, provide a small concrete implementation inside
  `workbench/` rather than an in-memory fixture class
- instantiate container-registered classes through `$this->app->make()` rather than `new`; this
  exercises the full dependency chain, respects application-level overrides via `bindIf()`, and
  keeps integration tests consistent with how the class is resolved at runtime

### Unit tests

Tests in `tests/Unit/` cover isolated class behavior.

Rules:

- prefer plain `PHPUnit\Framework\TestCase` whenever Laravel infrastructure is not needed
- only use the package `tests/TestCase.php` when container, config, or filesystem integration is
  genuinely required
- do not use PHPUnit mocks or stubs
- use concrete workbench classes as collaborators when needed

Typical unit-test targets in this package:

- DTOs and value objects
- request/context orchestration classes
- validators and authenticators exercised with concrete workbench collaborators
- configuration-free adapter logic

### Workbench as the test application

`workbench/` is the single source of concrete test-support code. It is a real Laravel application
and must be treated as one: classes there follow the same quality standards as production source.

Workbench provides:

- a real `User` model and factory (`workbench/app/Models/User.php`,
  `workbench/database/factories/UserFactory.php`)
- a real Filament panel configuration (`workbench/app/Providers/Filament/AdminPanelProvider.php`)
- a real service provider for package registration in tests
  (`workbench/app/Providers/WorkbenchServiceProvider.php`)
- real routes and bootstrap used by the test HTTP kernel

When a test requires a concrete collaborator that does not yet exist in `workbench/`, it is built
there as a real class — not as a mock and not as a fixture in `tests/`.

### Tooling

The project uses PHPUnit for test execution.

Rules:

- write PHPUnit tests only
- do not introduce Pest tests
- local and CI execution invoke `vendor/bin/phpunit` or the existing Composer script wrappers

## Consequences

Positive consequences:

- test names, folders, and execution style match the actual architectural layer being verified
- feature tests provide confidence in the real HTTP entrypoint and request pipeline
- integration tests verify real framework and filesystem behavior
- unit tests stay small, fast, and easy to understand
- the suite reflects the package's contract-driven architecture; behavior is always verified through
  real code paths

Trade-offs:

- concrete workbench implementations take more upfront effort than a quick mock
- integration tests that use real filesystem or database services require more setup
- strict layer discipline requires moving tests when implementation boundaries become clearer

Operational consequence:

- new tests are reviewed not only for correctness, but also for correct layer placement and for
  whether they use real code paths instead of mocks
