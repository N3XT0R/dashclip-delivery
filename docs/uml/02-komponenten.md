# Komponenten

Die Anwendung ist in Schichten aufgebaut. Eingänge (Controller, Bereiche, Befehle, Listener) rufen
Anwendungsfälle und Dienste auf, die über Repositories auf die Modelle zugreifen. Lange Arbeiten laufen
als Jobs in der Warteschlange.

```mermaid
flowchart TB
    subgraph eingang[Eingänge]
        web[Öffentliche Website<br/>Blade-Views, BlogController,<br/>PublicDocumentController]
        offer[Angebotsseite per Mail-Link<br/>OfferController, ZipController,<br/>AssignmentDownloadController]
        standard[Bereich für Einsender und Kanäle<br/>Filament Standard: MyOffers,<br/>VideoResource, ChannelWorkspace]
        adminp[Verwaltungsbereich<br/>Filament Admin: Resources, Widgets]
        api[REST-API v1<br/>Api-Controller, OpenApi]
        cli[Konsole und Zeitplan<br/>app/Console/Commands]
        listeners[Ereignis-Listener<br/>app/Listeners]
    end

    subgraph anwendung[Anwendungsschicht]
        usecases[Anwendungsfälle<br/>app/Application<br/>UploadVideoUseCase, ReturnAssignment,<br/>ApproveChannelApplication, ...]
        services[Dienste<br/>app/Services<br/>AssignmentDistributor, OfferNotifier,<br/>ZipService, MailService, ...]
        pipeline[Ingest-Pipeline<br/>app/Pipelines/Ingest]
        jobs[Jobs<br/>ProcessVideoIngestJob, BuildZipJob,<br/>BuildOfferExportZipJob, ProcessWebDavZipJob]
        events[Ereignisse<br/>app/Events]
    end

    subgraph daten[Datenzugriff]
        repos[Repositories<br/>app/Repository]
        models[Eloquent-Modelle<br/>app/Models]
    end

    subgraph extern[Externe Systeme]
        db[(MariaDB)]
        redis[(Redis<br/>Cache, Queue, Sessions)]
        storage[(Dateispeicher<br/>lokal, Dropbox, WebDAV)]
        mail[Mailserver<br/>SMTP und IMAP]
        reverb[Reverb<br/>WebSockets]
    end

    web --> services
    offer --> services
    standard --> usecases
    standard --> services
    adminp --> usecases
    adminp --> services
    api --> usecases
    api --> services
    cli --> services
    cli --> usecases
    listeners --> jobs
    listeners --> services

    usecases --> services
    usecases --> repos
    usecases -- löst aus --> events
    services --> repos
    services -- stellt ein --> jobs
    jobs --> pipeline
    jobs --> services
    pipeline -- löst aus --> events
    events --> listeners

    repos --> models
    models --> db
    jobs -. Warteschlange .-> redis
    services --> storage
    services --> mail
    services -. Fortschritt und Glocke .-> reverb
```

## Querschnittskomponenten

```mermaid
flowchart LR
    subgraph auth[Authentifizierung und Rechte]
        guards[Guards<br/>web, standard, api]
        roles[Rollen<br/>super_admin, panel_user,<br/>channel_operator]
        policies[Policies und Berechtigungen<br/>pro Team]
        passport[OAuth-Server<br/>Scopes wie offers:read]
        passkeys[Passkeys]
    end

    subgraph beobachtung[Nachvollziehbarkeit]
        activity[Aktivitätsprotokoll]
        maillog[MailLog]
        downloads[Download-Historie]
        clicks[OfferLinkClick]
    end

    subgraph config[Konfiguration]
        cfg[Config-Modell<br/>Kategorien und Werte]
        chcfg[ChannelConfig<br/>pro Kanal]
    end

    guards --> roles --> policies
    passport --> guards
    passkeys --> guards
```
