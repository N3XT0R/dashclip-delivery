# Ingest-Pipeline

Nach dem Upload verarbeitet `ProcessVideoIngestJob` jedes Video über eine Pipeline aus Schritten. Die
Schritte werden im `IngestServiceProvider` mit dem Tag `ingest.step` registriert. Jeder Schritt meldet
seinen Stand an den `IngestStateService`, damit ein abgebrochener Lauf an der richtigen Stelle
weitermacht.

## Struktur

```mermaid
classDiagram
    direction LR

    class IngestStepInterface {
        <<interface>>
        +name() IngestStepEnum
        +dependsOn() array
        +isApplicable(IngestContext) bool
        +handle(IngestContext) IngestContext
    }
    class ValidateInputFileStep
    class LookupAndUpdateVideoHashStep
    class GeneratePreviewForVideoClipsStep
    class UploadVideoToDropboxStep

    class IngestPipeline {
        -iterable steps
        +getSteps() iterable
        +handle(IngestContext) IngestContext
    }
    class IngestContext {
        +Video video
        +bool isDuplicate
        +bool isInvalid
    }
    class IngestStateService {
        +markProcessingStatus(Video, ProcessingStatusEnum)
        +isStepCompleted(Video, IngestStepEnum) bool
        +dependenciesAreCompleted(Video, array) bool
        +markStepRunning(Video, IngestStepEnum)
        +markStepCompleted(Video, IngestStepEnum)
        +markStepFailed(Video, IngestStepEnum, Throwable)
    }
    class ProcessVideoIngestJob {
        +int videoId
        +handle(VideoRepository, IngestPipeline)
    }

    IngestStepInterface <|.. ValidateInputFileStep
    IngestStepInterface <|.. LookupAndUpdateVideoHashStep
    IngestStepInterface <|.. GeneratePreviewForVideoClipsStep
    IngestStepInterface <|.. UploadVideoToDropboxStep
    IngestPipeline o-- "4" IngestStepInterface
    IngestPipeline --> IngestStateService
    IngestPipeline ..> IngestContext
    ProcessVideoIngestJob --> IngestPipeline
```

## Ablauf eines Laufs

```mermaid
flowchart TD
    start([ProcessVideoIngestJob]) --> load{Video vorhanden?}
    load -- nein --> ende([Ende])
    load -- ja --> done{Status completed<br/>oder deleted?}
    done -- ja --> ende
    done -- nein --> running[Status auf running setzen]
    running --> next{Nächster Schritt?}
    next -- keiner mehr --> completed[Status auf completed setzen] --> ende
    next -- ja --> skip1{Schritt schon<br/>abgeschlossen?}
    skip1 -- ja --> next
    skip1 -- nein --> deps{Abhängigkeiten<br/>erledigt?}
    deps -- nein --> next
    deps -- ja --> appl{Schritt<br/>anwendbar?}
    appl -- nein --> next
    appl -- ja --> run[Schritt als running markieren<br/>und ausführen]
    run -- Fehler --> failed[Schritt und Video<br/>als failed markieren] --> rethrow([Ausnahme weiterwerfen,<br/>Job wird wiederholt])
    run -- erfolgreich --> stepdone[Schritt als completed markieren]
    stepdone --> dup{Duplikat oder<br/>ungültig?}
    dup -- ja --> completed
    dup -- nein --> next
```

| Schritt                          | Aufgabe                                                      |
|----------------------------------|--------------------------------------------------------------|
| `ValidateInputFileStep`          | Prüft, ob die Datei ein gültiges Video ist                   |
| `LookupAndUpdateVideoHashStep`   | Berechnet den SHA-256-Hash und erkennt Duplikate             |
| `GeneratePreviewForVideoClipsStep` | Erzeugt die Vorschau für jeden Clip mit FFmpeg             |
| `UploadVideoToDropboxStep`       | Lädt das Video in den Zielspeicher hoch                      |

Hängen gebliebene oder fehlgeschlagene Läufe stellen die Befehle unter
`app/Console/Commands/VideoProcessing` alle 15 Minuten neu ein.
