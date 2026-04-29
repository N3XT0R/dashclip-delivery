# 0005. Method-Level PHPDoc And Import-Based Type References

## Status

Accepted

## Context

This Laravel video-sharing application is intentionally structured around explicit contracts,
domain-specific exceptions, DTOs, value objects, services, repositories, queue jobs, Filament
resources, ingest pipelines, and replaceable runtime collaborators.

That architecture only helps contributors if method-level behavior is understandable at the point
of use. Type declarations alone are not always sufficient for that in this project because they do
not explain:

- what a public method is responsible for in application terms
- what each parameter means in the current runtime flow
- which exceptions are part of the method contract
- what array and collection contents actually contain
- how a caller should interpret framework, Filament, queue, storage, mail, or provider boundary
  methods

Without a consistent documentation style, several forms of drift appear:

- public APIs become readable only by tracing implementations manually
- array and collection shapes remain implicit and error-prone
- exception contracts are known only from implementation details
- docblocks alternate between helpful application-oriented descriptions and generic noise
- comments start using fully qualified class names directly, which makes them harder to scan and
  duplicates import information already handled by the language

The project already favors explicitness in naming, contracts, exception hierarchies, and runtime
boundaries. Method-level documentation must follow that same direction while staying compatible with
Laravel's normal PHPDoc style.

## Decision

All public methods in maintained application code must include a method-level PHPDoc block when the
method is part of the project's maintained code surface and the signature alone does not communicate
the practical contract clearly.

The required documentation style is:

- explain what the method does in direct, contributor-oriented language
- document each parameter when its semantic role is not obvious from the type and name alone
- document relevant thrown exceptions when they are part of the callable contract
- document the return value when the caller needs more information than the native return type
  provides
- describe concrete contents for arrays and collections, for example `list<Video>`,
  `Collection<int, Assignment>`, or `array<string, mixed>`

The goal is that a contributor can understand the practical contract of a public method from its
signature and PHPDoc without immediately opening the implementation.

Redundant `@param` and `@return` tags may be omitted when native PHP types and method names already
make the contract clear. Generic arrays, Laravel collections, iterable values, DTO payloads, and
domain-specific exceptions must be documented explicitly.

### Type-reference rule for docblocks

When a docblock refers to a class, interface, exception, collection item type, or generic type
parameter, it must prefer imported short names over fully qualified class names whenever the
language import system can express that reference clearly.

Required direction:

- import the type with a `use` statement
- reference the short class name inside the PHPDoc

Preferred:

```php
use App\DTO\Channel\ApplicationMetaDto;
use App\Models\Video;
use Illuminate\Support\Collection;

/**
 * Return the videos visible in the current channel context.
 *
 * @return Collection<int, Video>
 */
public function visibleVideos(): Collection
{
    // ...
}

/**
 * Build application metadata for the channel application flow.
 *
 * @return array<string, ApplicationMetaDto>
 */
public function applicationMetadata(): array
{
    // ...
}
```

Forbidden:

```php
/**
 * @return \Illuminate\Support\Collection<int, \App\Models\Video>
 */
public function visibleVideos(): \Illuminate\Support\Collection
{
    // ...
}
```

Boundary notes:

- scalar types, native arrays, and shapes such as `array<string, mixed>` do not require imports
- union and nullable native types should stay in the method signature whenever PHP can express them
- if a type cannot be imported meaningfully, use the clearest available notation, but imported short
  names remain the default rule
- imported short names must not create ambiguity; use aliases when two classes have the same short
  name

## Scope

This ADR is immediately normative for:

- new public methods
- existing public methods that are materially touched during refactors or feature work
- public methods on services, repositories, use cases, DTOs, value objects, jobs, commands,
  pipeline steps, exception classes, Filament support classes, and framework integration classes

Private and protected methods may also be documented when that materially improves readability, but
the hard requirement applies only to public methods that form part of the maintained application
surface.

Generated framework methods and conventional Laravel or Filament methods may use concise docblocks
when the framework contract is already well known, but arrays, collections, domain exceptions, and
non-obvious behavior still need explicit documentation.

## Rules

1. **Public method documentation explains intent**

   A public method PHPDoc block must explain the method's application-level responsibility. It should
   not merely repeat the method name in sentence form.

2. **Document semantic parameters**

   Parameters should be documented when their role is domain-specific, when multiple parameters have
   similar primitive types, or when the valid value range is not obvious.

3. **Document generic arrays and collections**

   Any public method returning or accepting `array`, `iterable`, `Collection`, `LengthAwarePaginator`,
   or similar container types must document the contained shape or item type.

4. **Document callable exception contracts**

   Domain-specific exceptions that callers are expected to handle must be listed with `@throws`.
   Incidental low-level exceptions should be translated at the boundary according to ADR 0004 before
   becoming part of a public contract.

5. **Use imports for class references**

   Docblocks must use imported short names for classes, interfaces, exceptions, and generic item
   types whenever practical. Fully qualified class names in docblocks are reserved for rare cases
   where importing would be misleading or impossible.

6. **Avoid generic noise**

   Docblocks should add useful contract information. They must not become boilerplate that simply
   repeats native types, variable names, or obvious implementation details.

7. **Keep docs synchronized with signatures**

   When a method signature changes, the PHPDoc must be updated in the same change. Stale `@param`,
   `@return`, or `@throws` tags are treated as defects.

## Consequences

Positive consequences:

- application APIs become easier to understand directly in the IDE
- parameter meaning and exception behavior become visible without implementation tracing
- array and collection contracts become explicit and reviewable
- docblocks stay shorter and more readable because they use imported short names instead of fully
  qualified class names
- documentation style aligns with the project's broader preference for explicit,
  intention-revealing code
- Laravel's convention of avoiding redundant docblocks remains available where native types are
  already sufficient

Trade-offs:

- every public API change carries an additional documentation obligation
- refactors that touch public methods may require extra import cleanup
- reviewers must enforce documentation quality instead of only checking for the existence of a
  docblock
- overly broad documentation requirements can create noise if contributors do not keep the
  "useful contract information" standard in mind

Rejected alternatives:

- **Rely on native PHP types only**: rejected because parameter meaning, exception behavior, and
  collection contents would remain underspecified.
- **Document only externally published APIs**: rejected because maintainability also depends on
  internal public method surfaces being understandable.
- **Require full `@param` and `@return` tags for every method**: rejected because Laravel style allows
  redundant tags to be omitted when native types already communicate the contract.
- **Allow fully qualified names freely in docblocks**: rejected because they create unnecessary
  visual noise and duplicate namespace information already modeled through imports.
