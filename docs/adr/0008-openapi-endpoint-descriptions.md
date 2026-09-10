# 0008. OpenAPI Endpoints Must Carry a Description, Not Only a Summary

## Status

Accepted

## Context

The project exposes a public REST API (Ticket #250) documented with OpenAPI 3.0 through
`darkaonline/l5-swagger`, using PHP attributes (`#[OA\Get]`, `#[OA\Post]`, …) on the controllers
under `app/Http/Controllers/Api/V1`. The rendered Swagger UI at `/api/documentation` and the raw
spec at `/docs` are the primary reference for API consumers, most of whom are external and have no
access to the source code.

OpenAPI operations support two prose fields:

- `summary` — a short label, shown as the collapsed operation title in Swagger UI. It answers
  "which endpoint is this".
- `description` — a longer, CommonMark-capable text, shown when the operation is expanded. It
  answers "what does calling this actually do".

A `summary` alone (for example "Delete a video") tells a reader the name of the operation but not
its behaviour: what is affected, what the visibility and authorization rules are, what side effects
occur, which fields are writable, what status codes mean, and how asynchronous work (such as the
ingest pipeline) is surfaced. Without that, consumers have to guess, read our source, or discover
behaviour by trial and error against a live system.

Because the annotations live next to the code they describe, keeping a real description current is
cheap at write time and expensive to retrofit later once many endpoints exist.

## Decision

Every documented OpenAPI operation in this project must define a non-empty `description` in
addition to its `summary`.

### Scope

This applies to every `#[OA\Get]`, `#[OA\Post]`, `#[OA\Put]`, `#[OA\Patch]`, `#[OA\Delete]`,
`#[OA\Head]`, and `#[OA\Options]` attribute that contributes a path operation to the generated
spec, in any current or future API version namespace.

### Content requirements

The `description` is written for an external API consumer and must, where applicable to the
endpoint:

- state what the operation does and what resource or state it affects, in plain language;
- state the visibility / ownership scope — which records the caller can act on and why (for
  example "the same set the user sees in the Standard panel");
- name any non-obvious side effects (queued jobs, batch creation, file storage, cascading
  deletes);
- call out which request fields are writable when that is deliberately restricted, and what
  happens to the rest;
- explain meaningful status codes beyond the obvious success case (for example why a foreign
  record returns 404 rather than 403, or when 422 is returned for reasons other than field
  validation);
- describe how to observe follow-up state for asynchronous work (for example "poll the show
  endpoint for the processing status").

The `summary` stays short (a noun phrase or a single imperative clause). Detail belongs in the
`description`, not the `summary`.

### Style

- `description` text follows the same line-length and wrapping conventions as the surrounding
  PHP code.
- Use CommonMark sparingly (inline code for field and value names); do not embed large tables or
  headings in an operation description.
- Do not restate the parameter and schema documentation verbatim; describe behaviour and intent
  that the machine-readable parts cannot express.

## Rules

1. **No operation without a description**

   An `#[OA\*]` path-operation attribute that has a `summary` but no `description`, or an empty
   `description`, is a defect and must not be merged.

2. **Description describes behaviour**

   A `description` that merely repeats the `summary` in more words does not satisfy this ADR. It
   must add information a caller cannot get from the `summary`, the parameters, and the response
   schemas alone.

3. **Enforced by test**

   The API test suite asserts that every operation in the generated spec has a non-empty
   `description` (`OpenApiDocumentationTest::testEveryDocumentedOperationHasADescription`). New
   endpoints therefore fail CI until documented.

4. **Reviews check quality, tests check presence**

   The automated test guarantees a description exists; code review is responsible for judging
   whether it is accurate and useful per the content requirements above.

5. **Shared components still need their own prose**

   Reusable response, parameter, and schema components (`app/OpenApi/**`) should carry a
   `description` where the component's meaning is not obvious from its name and shape.

## Consequences

Positive consequences:

- the Swagger UI and the raw spec become a sufficient reference for external consumers without
  access to the source;
- behaviour that is easy to forget (async ingest, 404-not-403, write-field restrictions, batch
  creation) is captured at the point it is written;
- generated client SDKs and API catalogues carry meaningful per-operation documentation;
- onboarding for new API consumers and for new contributors touching the API is faster.

Trade-offs:

- each new endpoint costs a few extra lines of prose and the thought to write them;
- descriptions can drift from behaviour if not maintained; the presence test does not catch
  staleness, only absence, so reviewers must verify accuracy when an endpoint changes;
- the attribute blocks on controllers grow longer.

Rejected alternatives:

- **Summary only**: rejected because a label is not documentation for an external audience.
- **Descriptions in a separate hand-written guide**: rejected because a second source of truth
  drifts from the code and from the generated spec, and is not delivered with the machine-readable
  document that tooling consumes.
- **Generating descriptions from controller PHPDoc**: rejected because the audiences differ — ADR
  0005 PHPDoc targets contributors and describes the PHP contract, while an OpenAPI `description`
  targets API consumers and describes HTTP behaviour.
