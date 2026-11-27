# Abläufe

## Upload und Verarbeitung

```mermaid
sequenceDiagram
    autonumber
    actor E as Einsender
    participant UI as Bereich für Einsender
    participant UC as UploadVideoUseCase
    participant DB as Datenbank
    participant EV as VideoQueuedForIngest
    participant L as DispatchVideoIngestJobListener
    participant Q as Horizon
    participant J as ProcessVideoIngestJob
    participant P as IngestPipeline
    participant N as NotifyUserVideoCompleted

    E->>UI: Datei, Start und Ende wählen
    UI->>UC: handle(file, startSec, endSec, user)
    UC->>DB: Video und Clip anlegen (Transaktion)
    UC-)EV: dispatch(video, user)
    EV->>L: handle()
    L->>Q: ProcessVideoIngestJob einstellen
    UC-->>UI: Video
    UI-->>E: "Wird verarbeitet"
    Q->>J: handle()
    J->>P: handle(IngestContext)
    loop Schritte
        P->>P: prüfen, validieren, Hash, Vorschau, Hochladen
    end
    P-)N: VideoCompleted
    N-->>E: Benachrichtigung und Mail
```

WebDAV-Uploads laufen über `ZipUploadedListener` und `ProcessWebDavZipJob`: das ZIP wird entpackt,
und jede enthaltene Datei geht in denselben Ablauf.

## Wochenlauf: verteilen und benachrichtigen

```mermaid
sequenceDiagram
    autonumber
    participant S as Zeitplaner (Mo 08:00)
    participant W as weekly:run
    participant X as AssignmentRepository
    participant D as AssignmentDistributor
    participant N as OfferNotifier
    participant M as MailService
    actor K as Kanal

    S->>W: starten
    W->>W: assign:uploader
    W->>X: assign:expire
    X->>X: fällige Angebote auf expired,<br/>ChannelVideoBlock setzen
    W->>D: assign:distribute
    D->>D: Batch(assign) starten
    D->>D: Pool sammeln, nach Uploader teilen,<br/>Bundles bilden, Kanäle wählen
    D->>X: Angebote (queued) anlegen
    W->>W: videos:mark-distributed
    W->>N: notify:offers
    N->>X: bereite Angebote je Kanal auf notified,<br/>expires_at setzen
    N->>M: sendNewOfferMail(channel, batch, expiry)
    M-->>K: Mail mit signiertem Angebotslink
    N->>N: Batch(notify) mit Mail-Zahl anlegen
```

## Angebotsseite aus der Mail

```mermaid
sequenceDiagram
    autonumber
    actor K as Kanal
    participant OC as OfferController
    participant ZC as ZipController
    participant PS as OfferDownloadPreparationService
    participant Q as Horizon
    participant BJ as BuildZipJob
    participant ZS as ZipService
    participant C as DownloadCacheService
    participant R as Reverb

    K->>OC: GET /offer/{batch}/{channel} (signiert)
    OC-->>K: Angebotsliste mit Vorschau
    K->>ZC: POST /zips/{batch}/{channel} (Auswahl)
    ZC->>PS: prepare(request, channel, ids, batch)
    PS->>C: init(jobId)
    PS->>Q: BuildZipJob einstellen
    PS-->>K: jobId, Einzel-Links, progressUrl, downloadUrl
    Q->>BJ: handle()
    BJ->>BJ: nicht verfügbare Angebote als skipped melden
    BJ->>ZS: build(batch, channel, items, jobId)
    loop jede Datei
        ZS->>C: Dateistatus setzen
        ZS-)R: ZipProgressUpdated
        R-)K: Fortschritt
    end
    ZS->>ZS: info.csv anlegen, ZIP schließen
    ZS->>C: Status ready
    K->>ZC: GET /zips/{id}/download
    ZC->>ZC: Angebote als picked_up markieren,<br/>Download-Einträge schreiben
    ZC-->>K: ZIP-Datei
    K->>OC: POST /offer/{batch}/{channel}/unused
    OC-->>K: nicht verwendete Videos auf rejected
```

## Export in "Meine Angebote"

```mermaid
sequenceDiagram
    autonumber
    actor K as Kanalbetreiber
    participant MO as MyOffers (Filament)
    participant EX as OfferExporter
    participant Q as Horizon
    participant BJ as BuildOfferExportZipJob
    participant ZS as ZipService
    participant FS as OfferExportFileService
    participant CJ as OfferExportCompletionJob
    participant NO as OfferDownloadReadyNotification
    participant DC as OfferExportDownloadController

    K->>MO: Angebote wählen, "Herunterladen"
    MO->>EX: Export starten (channel_id)
    MO-->>K: "Download wird vorbereitet"
    EX->>Q: BuildOfferExportZipJob einstellen
    Q->>BJ: handle()
    BJ->>ZS: buildArchive(path, channel, items, jobId)
    ZS-->>BJ: gepackte Angebote
    BJ->>FS: writePackedIds(export, ids)
    BJ->>BJ: Zeilenzahlen am Export speichern
    Q->>CJ: handle()
    CJ->>NO: notify(user)
    NO-->>K: Glocke (Reverb) und Mail "Download bereit"
    K->>DC: GET /offers/exports/{id}/download
    DC->>DC: gepackte Angebote als picked_up markieren
    DC-->>K: ZIP mit Videos und info.csv
```

## Kanalzugang beantragen und freigeben

```mermaid
sequenceDiagram
    autonumber
    actor E as Einsender
    actor A as Admin
    actor K as Kanal-Mailadresse
    participant CA as CreateChannelApplication
    participant AA as ApproveChannelApplication
    participant T as ActionToken
    participant H as HandleChannelAccessApproval
    participant AC as ApproveChannelAccess

    E->>CA: Antrag für neuen oder bestehenden Kanal
    CA->>CA: ChannelApplication(pending)
    A->>AA: Antrag auf approved setzen
    alt neuer Kanal
        AA->>AA: Kanal anlegen, Rolle channel_operator vergeben
    else bestehender Kanal
        AA-)K: ChannelAccessRequested, Mail mit Bestätigungslink
        K->>T: Link öffnen und bestätigen
        T-)H: ActionTokenConsumed
        H->>AC: handle(application)
    end
    AC->>AC: Benutzer dem Kanal zuordnen, Antrag approved
    AC-->>E: ChannelAccessApprovedNotification
```

## REST-API mit OAuth

```mermaid
sequenceDiagram
    autonumber
    participant C as API-Client
    participant O as OAuth-Server
    participant MW as Middleware
    participant AC as Api-Controller
    participant S as Dienste und Anwendungsfälle

    C->>O: Token anfordern (Scopes z. B. offers:read)
    O-->>C: Access-Token
    C->>MW: GET /api/v1/offers (Bearer)
    MW->>MW: auth:api, scope:offers:read,<br/>standard.permission:View:MyOffers
    alt nicht berechtigt
        MW-->>C: 401 oder 403
    else berechtigt
        MW->>AC: index()
        AC->>S: Angebote laden, filtern, sortieren
        S-->>AC: Daten
        AC-->>C: 200 JSON
    end
```
