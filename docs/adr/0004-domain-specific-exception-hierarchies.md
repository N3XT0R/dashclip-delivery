# 0004. Domain-Specific Exception Hierarchies

## Status

Accepted

## Context

This Laravel video-sharing application already defines application-specific exceptions for concerns
such as file IO, missing video files, preview generation, and invalid time ranges.

That is the correct direction, but application-specific exception classes alone are not sufficient
if concrete exceptions remain flat or if code throws generic global exceptions such as
`\DomainException`, `\InvalidArgumentException`, or `\RuntimeException` directly for failures that
belong to the application's own domain.

Global SPL exceptions are too unspecific for this architecture:

- they do not communicate which bounded context or subsystem failed
- they make failure classification harder in reviews, logging, user-facing error handling, and queue
  failure analysis
- they encourage technical rather than domain-oriented modeling of error conditions
- they flatten distinct application concerns such as ingest, preview generation, file IO, uploads,
  zip handling, channel access, assignment distribution, and external provider integration into
  generic exception buckets

This project is intentionally structured around explicit domains, services, repositories, DTOs,
value objects, jobs, and pipeline steps. Its exception model must follow that same structure so that
failures are classified in a way that matches the application architecture.

## Decision

All domain-specific exceptions in this application must inherit from an application-owned exception
base class.

In addition, domain-specific exceptions must be organized hierarchically by bounded context or
subsystem instead of forming one flat list directly beneath a single generic base.

Required direction:

- an application exception root is the base for application-owned domain failures
- each domain or subsystem must define an intermediate exception base when it has multiple concrete
  failures
- concrete exceptions must inherit from the most specific domain base available

Example target hierarchy:

- `ApplicationException`
  - `FileSystemException`
    - `FileNotFoundException`
    - `FileReadException`
    - `VideoFileNotFoundException`
  - `PreviewException`
    - `PreviewGenerationException`
  - `VideoException`
    - `InvalidTimeRangeException`
  - `IngestException`
  - `AssignmentException`
  - `ChannelException`
  - `UploadException`
  - `ZipException`

The exact intermediate class names may follow the namespace and domain already used by the feature.
For example, exceptions under `App\Exceptions\IO` may use an `IoException` or
`FileSystemException` base as long as the name communicates the subsystem clearly and consistently.

The project must not throw or expose raw global exceptions such as:

- `\DomainException`
- `\InvalidArgumentException`
- `\RuntimeException`
- other generic SPL or framework exception types when the failure belongs to an application domain
  context

Instead, such failures must be translated into a domain-scoped application exception that
communicates the correct architectural context.

Normative implications:

- do not throw `\InvalidArgumentException` for invalid video clip boundaries; throw a video- or
  clip-scoped exception
- do not throw `\RuntimeException` for preview generation failure; throw a preview-scoped exception
- do not throw `\DomainException` directly; throw an application exception beneath the appropriate
  domain branch
- when input validation, state validation, or invariant failure is domain-specific, model it in the
  corresponding exception hierarchy
- only use non-domain exception types when the exception truly belongs to an external protocol,
  framework, or infrastructure boundary and must remain that native type

This rule is immediately normative for all new code. Existing flat or overly generic application
exceptions must be migrated toward domain hierarchies when materially touched or during dedicated
cleanup work.

## Rules

1. **Application failures use application exception types**

   If the application can classify the failure in domain language, it must use an application-owned
   exception type instead of a generic SPL exception.

2. **Subsystems get intermediate bases when they grow**

   A subsystem with multiple concrete exceptions must define an intermediate base exception for that
   subsystem. This allows callers to catch a whole failure family without losing domain meaning.

3. **Concrete exceptions use the most specific base**

   A concrete exception must extend the nearest meaningful subsystem base. It should not skip directly
   to the root application exception when a more specific base exists.

4. **Infrastructure exceptions are translated at boundaries**

   Vendor, filesystem, queue, HTTP client, or framework exceptions should be caught at the boundary
   where useful context is available and translated into an application exception. The original
   exception should be preserved as the previous exception when that helps debugging.

5. **Validation exceptions stay in their correct layer**

   Laravel validation errors may remain Laravel validation exceptions at HTTP or Filament input
   boundaries. Domain invariant failures inside services, use cases, pipeline steps, or value objects
   must use domain-scoped application exceptions.

6. **Messages explain the instance; types classify the failure**

   Exception messages should describe what happened in the concrete case. Code must not rely on
   parsing message text to classify the failure category.

7. **Existing exceptions are migrated deliberately**

   Existing exceptions such as file IO, preview generation, and invalid time range failures should be
   moved under clear subsystem bases when those areas are materially touched. Renames and namespace
   changes must include the related tests or framework discovery checks needed to prove behavior.

## Consequences

Positive consequences:

- failures become classifiable by application context instead of only by generic technical category
- exception handling, logging, and queue failure analysis gain a more precise scope
- reviews can reason about failure semantics from the type hierarchy alone
- the exception model becomes consistent with the project's SOLID and domain-driven architectural
  direction
- broader catch points remain possible without losing domain meaning, for example catching
  `FileSystemException` or `PreviewException`

Trade-offs:

- more exception classes may be needed because each domain can require its own intermediate base type
- legacy flat exception trees introduce incremental migration work
- authors must think about failure classification earlier instead of defaulting to convenient global
  exceptions
- some framework-level exceptions still need to remain native at Laravel or Filament boundaries

Rejected alternatives:

- **Allow direct use of `\DomainException`, `\InvalidArgumentException`, and `\RuntimeException`**:
  rejected because they do not provide a meaningful application-specific scope.
- **Keep only one flat application exception layer without intermediate branches**: rejected because
  it weakens failure classification once multiple exceptions exist in the same subsystem.
- **Rely on message text instead of type hierarchy for classification**: rejected because messages
  are less stable, less enforceable, and weaker for typed handling.
- **Wrap every framework exception immediately**: rejected because Laravel validation, authorization,
  and HTTP exceptions are valid framework boundary types when they are still operating at that
  boundary.
