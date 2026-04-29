# 0003. SOLID Compliance And Established Design Patterns

## Status

Accepted

## Context

This Laravel video-sharing application is built around explicit responsibilities, replaceable
collaborators, and framework extension points. It contains HTTP controllers, Filament resources,
application use cases, services, repositories, DTOs, value objects, queue jobs, mailables,
notifications, observers, listeners, ingest pipelines, storage integrations, and external-service
boundaries.

As the number of classes, runtime decisions, and integration points grows, the cost of ad hoc design
also grows.

Without a shared design standard, several problems appear quickly:

- responsibilities become mixed across controllers, Filament resources, services, repositories,
  jobs, pipeline steps, and external adapters
- extension work starts to require modifications in existing classes instead of adding new
  implementations
- abstractions stop matching the concrete behavior they are supposed to hide
- interfaces become too broad and force consumers to depend on methods they do not need
- concrete framework, storage, mail, queue, or vendor details leak into parts of the code that should
  stay decoupled
- teams begin to solve the same recurring design problems with one-off custom structures instead of
  recognizable patterns

The project already uses object-oriented collaboration shapes that naturally align with well-known
design principles and patterns. Examples already present in the codebase include repository-style
data access, service-layer business logic, pipeline steps for video ingest, DTOs for data transport,
value objects for structured values, provider classes for framework registration, facade-style
access to subsystems, and decorator-style behavior around notification discovery.

## Decision

All new code and all materially touched refactors must be designed to comply with SOLID principles.

The normative expectations are:

- **Single Responsibility Principle**: each class or module must have one clear reason to change.
  HTTP entry, Filament UI configuration, authorization, validation, data access, storage access,
  queue execution, external API integration, and domain orchestration must stay separated.
- **Open/Closed Principle**: new behavior should preferably be introduced by adding implementations,
  collaborators, configuration, or composition points instead of modifying stable existing code.
- **Liskov Substitution Principle**: implementations behind contracts must remain behaviorally
  substitutable for the abstractions they implement.
- **Interface Segregation Principle**: interfaces must stay focused and client-specific rather than
  turning into wide "do everything" contracts.
- **Dependency Inversion Principle**: high-level policy and orchestration code must depend on
  abstractions where extension or replacement is intended, not directly on concrete infrastructure
  details.

In addition, when a recurring design problem clearly matches a known design pattern, the
implementation must prefer an established pattern over an ad hoc custom structure.

This includes, but is not limited to:

- **Factory / Factory Method / Abstract Factory**: for object creation that would otherwise couple
  client code to concrete classes or complex construction rules.
- **Strategy**: for interchangeable runtime behavior such as ingest steps, validation rules,
  authorization decisions, storage behavior, notification channels, or provider-specific logic.
- **Builder**: for stepwise construction of complex paths, payloads, queries, exports, archives, or
  workflow objects.
- **Adapter**: for isolating framework, filesystem, Dropbox, mail, queue, or vendor boundaries
  behind application-level contracts.
- **Facade**: for presenting a smaller, intention-revealing interface to a more complex subsystem,
  especially when the project already exposes that subsystem through Laravel facade conventions.
- **Decorator**: for extending behavior compositionally without modifying the wrapped
  implementation, for example caching, logging, instrumentation, or feature-specific enrichment.
- **Pipeline**: for ordered multi-step processing such as video ingest, cleanup, import, or export
  workflows where each step has a focused responsibility.

The preference is for recognized, widely understood patterns with established names and expectations.
Custom one-off structures are not preferred when a standard pattern already fits the same problem.

At the same time, patterns must not be introduced ceremonially:

- a pattern is required when it genuinely clarifies a recurring design problem
- a simpler direct implementation is still valid when no real pattern-level problem exists
- unnecessary abstraction layers must not be introduced just to claim pattern usage
- Laravel and Filament conventions remain valid when they already provide the appropriate structure

## Rules

1. **Controllers and Filament resources do not own business workflows**

   Controllers and Filament resources may validate input, authorize access, prepare UI schemas, and
   delegate work. Business decisions, persistence workflows, storage operations, ingest processing,
   and notification orchestration belong in application use cases, services, jobs, or dedicated
   collaborators.

2. **Repositories encapsulate data access**

   Repositories may contain query construction and persistence-specific decisions. They must not own
   user-interface behavior, HTTP behavior, queue behavior, or external API workflows.

3. **Services and use cases express application behavior**

   Services contain reusable business behavior. Use cases represent application actions with a clear
   input, output, and outcome. A use case may orchestrate repositories, services, jobs, and adapters,
   but should not become a general-purpose service container.

4. **Jobs execute prepared background work**

   Queue jobs should be small entrypoints for asynchronous execution. They may call services or use
   cases, but must not accumulate unrelated business rules simply because the work is queued.

5. **Pipeline steps stay independently replaceable**

   Pipeline steps must perform one meaningful operation and communicate through explicit context,
   DTOs, or value objects. A step must not silently depend on unrelated mutable global state when a
   clear dependency can be injected.

6. **Contracts are introduced at real extension boundaries**

   Interfaces are required when multiple implementations exist, replacement is expected, or the
   dependency crosses a meaningful architectural boundary. Interfaces are not required for every class
   by default.

7. **External systems stay behind boundaries**

   Dropbox, filesystem, mail, queue, Horizon, Reverb, Filament, Livewire, and other framework or
   vendor details must be kept at the edge of the relevant feature. Domain and orchestration code
   should receive intention-revealing collaborators instead of reaching directly into vendor APIs
   unless the framework class is the natural boundary.

8. **Existing Laravel conventions are not anti-patterns**

   Laravel service providers, Eloquent models, policies, observers, mailables, notifications, jobs,
   commands, events, and Filament resources are valid framework patterns. The project should use them
   idiomatically instead of wrapping them in unnecessary custom abstractions.

9. **Refactors must improve the responsibility boundary**

   A materially touched class should leave the codebase closer to this ADR. Small opportunistic
   cleanups are acceptable, but broad architectural rewrites must be justified by a concrete
   maintenance, correctness, or extension benefit.

## Consequences

Positive consequences:

- design discussions gain a shared vocabulary
- responsibilities stay clearer and more stable over time
- extension points become easier to add without destabilizing existing code
- framework and vendor details remain better contained at the edges
- new contributors can recognize intent from familiar principles and pattern shapes
- refactoring decisions become easier to justify and review
- Laravel and Filament conventions remain first-class parts of the architecture

Trade-offs:

- design work becomes more opinionated and review standards become stricter
- some solutions may require more classes or interfaces than a minimal ad hoc implementation
- incorrect or over-eager pattern application can increase complexity if the team is not disciplined
- legacy code may need gradual cleanup as features are touched

Rejected alternatives:

- **Allow each feature to choose its own design style**: rejected because it leads to inconsistency,
  weaker maintainability, and avoidable architectural drift.
- **Require only SOLID but stay neutral on patterns**: rejected because recurring problems benefit
  from a shared, conventional set of solutions and terminology.
- **Require a design pattern for every non-trivial class**: rejected because it encourages ceremony
  and over-engineering instead of clarity.
- **Wrap every Laravel feature in custom abstractions**: rejected because framework conventions are
  already recognizable design patterns and should only be abstracted when there is a real boundary or
  replacement need.
