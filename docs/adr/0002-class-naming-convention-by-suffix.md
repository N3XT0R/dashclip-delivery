# 0002. Class Naming Convention By Suffix

## Status

Accepted

## Context

This is a Laravel video-sharing application with Filament panels, queues, mail, notifications,
repositories, services, DTOs, value objects, ingest pipelines, and application-level use cases.
As the system grows, the number of classes, responsibilities, and dependencies increases. Without
clear naming conventions, inconsistency appears quickly, which makes readability, maintainability,
and onboarding more difficult.

The application is structured around recognizable responsibilities such as controllers, Filament
resources, repositories, services, DTOs, policies, jobs, observers, listeners, commands, pipeline
steps, notifications, and Laravel service providers. In this architecture, it must be possible to
recognize the role of a class directly from its name.

At the same time, class names must not repeat context that is already expressed by the namespace or
directory structure. Otherwise names become longer without adding information, and the code starts
encoding the same concept in two places.

## Decision

Classes must follow a clearly defined naming scheme in which the responsibility is indicated by a
role suffix, except where Laravel or Filament framework conventions intentionally use a shorter
domain name.

The suffix must match the actual architectural or framework role of the class. The goal is not to
force every class into a small generic list, but to make responsibilities explicit and consistent.

The prefix, when present, must add domain meaning that is not already provided by the namespace.

### Allowed and required suffixes

| Type                  | Suffix                   | Description                                      |
|-----------------------|--------------------------|--------------------------------------------------|
| Services              | `*Service`               | Contains reusable business logic                 |
| Use cases             | `*UseCase`               | Application action with one explicit outcome     |
| Repositories          | `*Repository`            | Encapsulates data access                         |
| Factory classes       | `*Factory`               | Creates objects                                  |
| Interfaces            | `*Interface`             | Defines contracts                                |
| Value objects         | `*ValueObject`           | Immutable domain values                          |
| Data transfer objects | `*Dto`                   | Data transport structure                         |
| Exceptions            | `*Exception`             | Error cases                                      |
| Controllers           | `*Controller`            | HTTP entrypoints                                 |
| Filament resources    | `*Resource`              | Filament resource definitions                    |
| Filament pages        | framework page name      | Filament page classes, e.g. `ListVideos`         |
| Filament widgets      | `*Widget`                | Filament dashboard widgets                       |
| Relation managers     | `*RelationManager`       | Filament relation managers                       |
| Policies              | `*Policy`                | Authorization rules                              |
| Abilities             | `*Ability`               | Named authorization ability objects              |
| Service providers     | `*ServiceProvider`       | Laravel service-provider integration             |
| Jobs                  | `*Job`                   | Queued background work                           |
| Commands              | `*Command`               | Console command entrypoints                      |
| Events                | past-tense event name    | Domain or application events                     |
| Event listeners       | `*Listener`              | Reacts to events                                 |
| Observers             | `*Observer`              | Eloquent model lifecycle observers               |
| Notifications         | `*Notification`          | Laravel notifications                            |
| Mailables             | `*Mail`                  | Laravel mail classes                             |
| Enums                 | `*Enum`                  | Enumerated values                                |
| Pipelines             | `*Pipeline`              | Ordered processing pipeline                      |
| Pipeline steps        | `*Step`                  | Single pipeline operation                        |
| Context objects       | `*Context`               | Runtime context passed through a workflow        |
| Resolvers             | `*Resolver`              | Runtime resolution logic                         |
| Builders              | `*Builder`               | Incremental object or path construction          |
| Importers             | `*Importer`              | Imports external structured data                 |
| Scanners              | `*Scanner`               | Scans external sources or messages               |
| Providers             | `*Provider`              | Provides external service or token integration   |
| Facades               | domain facade name       | Laravel facade classes in `app/Facades`          |
| Traits                | `*Trait`                 | Reusable implementation fragments                |
| Eloquent models       | domain noun              | Persistence-backed domain records in `app/Models` |
| Pivot models          | `*Pivot` when helpful    | Eloquent pivot records                           |
| View components       | domain component name    | Laravel view components                          |

### Examples

- `VideoService`
- `AssignmentRepository`
- `GetVideoIngestStatusUseCase`
- `ApplicationMetaDto`
- `AssignmentRunValueObject`
- `AssignmentDownloadController`
- `VideoResource`
- `VideoStatsOverviewWidget`
- `AssignmentsRelationManager`
- `AccessChannelPageAbility`
- `ProcessVideoIngestJob`
- `CleanUpDatabaseCommand`
- `VideoQueuedForIngest`
- `DispatchVideoIngestJobListener`
- `VideoObserver`
- `UserUploadProceedNotification`
- `ChannelWelcomeMail`
- `IngestPipeline`
- `ValidateInputFileStep`
- `IngestContext`
- `MailAddressResolver`
- `PathBuilder`
- `InfoImporter`
- `MailReplyScanner`
- `AutoRefreshTokenProvider`
- `User`
- `ChannelTeamPivot`

## Rules

1. **No deviation from the naming scheme for new code**

   New classes must use the approved role suffix or one of the explicitly listed framework-aligned
   naming forms in this ADR.

2. **Exactly one responsibility per class**

   The name must reflect the actual role. Mixed forms such as `VideoServiceRepository` are not
   allowed. Framework-specific roles must keep the suffix or naming form that matches the framework
   role:

   - Laravel provider: `*ServiceProvider`
   - Laravel command: `*Command`
   - Laravel job: `*Job`
   - Filament resource: `*Resource`
   - Filament relation manager: `*RelationManager`
   - Filament page: framework page name such as `CreateVideo`, `EditVideo`, `ListVideos`, `ViewVideo`

3. **Interface requirement for abstracted extension points**

   Every abstracted service, repository, resolver, builder, importer, scanner, provider, pipeline,
   or pipeline step should have a corresponding interface when that role is part of the application's
   extension surface.

   Examples:

   - `ConfigServiceInterface` / `ConfigService`
   - `ConfigRepositoryInterface` / `EloquentConfigRepository`
   - `IngestStepInterface` / `ValidateInputFileStep`

4. **No generic names**

   `Helper`, `Manager`, and `Util` are not allowed. Use a clear domain-specific role instead.

5. **Suffix is mandatory unless this ADR defines a framework-aligned exception**

   The domain is expressed through the prefix. Examples: `VideoService`, `AssignmentRepository`,
   `ChannelWelcomeMail`.

   The main exceptions are Laravel and Filament classes whose short names are idiomatic and already
   scoped by namespace, such as Eloquent models (`User`, `Video`, `Channel`) and Filament pages
   (`ListVideos`, `EditVideo`).

6. **Namespace context must not be duplicated in the class name**

   A class name must not repeat domain or subsystem terms that are already clearly expressed by its
   namespace or directory. Move classes into the correct sub-namespace instead of encoding that
   context again in the class name. Prefer the shortest name that is still unambiguous inside its
   namespace.

   Forbidden:

   - `DTO\Channel\ChannelChannelApplicationRequestDto`
   - `Services\Upload\UploadUploadService`
   - `Filament\Admin\Resources\Videos\VideoVideoResource`

   Preferred:

   - `DTO\Channel\ApplicationRequestDto`
   - `Services\Upload\UploadService`
   - `Filament\Admin\Resources\Videos\VideoResource`

7. **Names must not compensate for missing structure**

   If a class name needs a long package, panel, workflow, or subsystem prefix to be understandable,
   that usually indicates that the class belongs in a more specific namespace. Prefer moving the
   class to a better namespace over inventing longer names such as `AdminVideo*`, `StandardChannel*`,
   or `IngestVideo*` when that context is already architectural rather than semantic.

8. **Existing deviations are legacy, not precedent**

   Existing classes that predate this ADR may keep their current names until they are touched for a
   meaningful change. New code must follow this ADR immediately. When a legacy class is renamed, the
   change must be handled deliberately and covered by tests or framework-level discovery checks.

9. **Framework and library semantics take precedence**

   A class must not be renamed into a semantically wrong suffix just to satisfy a generic naming
   pattern. Examples of correct framework-aligned names:

   - `AppServiceProvider`, not `AppService`
   - `CleanUpDatabaseCommand`, not `CleanUpDatabaseService`
   - `ProcessVideoIngestJob`, not `ProcessVideoIngestService`
   - `VideoResource`, not `VideoService`
   - `ListVideos`, not `VideoListPage` when following Filament's resource page convention
   - `User`, not `UserModel`

## Consequences

Positive consequences:

- responsibilities are visible immediately
- code readability improves
- the project structure becomes more uniform
- refactoring becomes easier
- IDEs and tooling can reason more effectively about class roles
- class names become shorter and less repetitive
- Laravel and Filament conventions stay recognizable

Trade-offs:

- some classes may need to move into more specific namespaces before a good short name becomes
  available
- special cases have less naming flexibility
- the convention must be applied consistently
- the approved suffix list must evolve carefully when the architecture introduces a genuinely new
  role
- legacy class names may need gradual cleanup instead of one large rename

Rejected alternatives:

- **No fixed convention**: rejected because it leads to inconsistency and higher maintenance cost.
- **Annotations instead of naming conventions**: rejected because the role of a class is less visible
  in the code itself.
- **Force `*Model` for Eloquent models**: rejected because Laravel applications conventionally use
  short domain nouns for models.
- **Allow redundant prefixes for panel, workflow, or subsystem context**: rejected because namespaces
  already provide that context and repeating it in class names creates avoidable noise.
