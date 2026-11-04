# Zustände

## Angebot (`Assignment.status`, `StatusEnum`)

```mermaid
stateDiagram-v2
    [*] --> queued : Verteilung legt Angebot an
    queued --> notified : Benachrichtigung per Mail<br/>oder Download-Link erzeugt
    queued --> picked_up : Download
    notified --> picked_up : Download
    queued --> expired : Ablaufdatum erreicht
    notified --> expired : Ablaufdatum erreicht
    queued --> rejected : Rückgabe
    notified --> rejected : Rückgabe
    picked_up --> rejected : Rückgabe oder<br/>"nicht verwendet" gemeldet
    expired --> queued : nächste Runde für<br/>denselben Kanal
    rejected --> [*]
    picked_up --> [*]
    expired --> [*]

    note right of expired
        Beim Ablauf wird eine Sperre (ChannelVideoBlock)
        gesetzt, damit der Kanal das Video erst nach der
        Abkühlzeit wieder angeboten bekommt.
    end note
```

`queued` und `notified` gelten als bereit (`getReadyStatus`). `queued`, `notified` und `picked_up`
lassen sich zurückgeben (`getReturnableStatuses`).

## Videoverarbeitung (`Video.processing_status`, `ProcessingStatusEnum`)

```mermaid
stateDiagram-v2
    [*] --> pending : Upload gespeichert
    pending --> running : Ingest-Job startet
    running --> completed : alle Schritte erledigt,<br/>Duplikat oder ungültig
    running --> failed : Schritt wirft Fehler
    failed --> running : Requeue-Befehl oder Wiederholung
    running --> running : hängt, Requeue nach Zeitlimit
    completed --> deleted : Dateien bereinigt<br/>(PurgeDeletedVideosUseCase)
    failed --> deleted : Dateien bereinigt
    deleted --> [*]
```

## Kanalantrag (`ChannelApplication.status`, `ApplicationEnum`)

```mermaid
stateDiagram-v2
    [*] --> pending : Antrag gestellt
    pending --> approved : Admin gibt im<br/>Verwaltungsbereich frei
    pending --> rejected : Admin lehnt ab
    approved --> [*]
    rejected --> [*]
```

## ZIP-Erstellung (`DownloadStatusEnum`, im Download-Cache)

Gesamtstatus des Auftrags:

```mermaid
stateDiagram-v2
    [*] --> queued : Download angefordert
    queued --> preparing : ZipService startet
    preparing --> downloading : nächste Datei holen
    downloading --> downloaded : Datei lokal vorhanden
    preparing --> downloaded : Datei schon lokal
    downloaded --> packing : Datei ins ZIP schreiben
    packing --> downloading : weitere Datei
    packing --> ready : ZIP geschlossen
    queued --> failed : Job nicht einstellbar
    preparing --> failed : Fehler oder alles übersprungen
    downloading --> failed
    packing --> failed
    ready --> [*]
    failed --> [*]
```

Status je Datei:

```mermaid
stateDiagram-v2
    [*] --> queued
    queued --> downloading
    queued --> downloaded : schon lokal
    downloading --> downloaded
    downloaded --> packing
    packing --> ready
    queued --> skipped : Angebot nicht mehr verfügbar
    ready --> [*]
    skipped --> [*]
```

## Lauf (`Batch.type`, `BatchTypeEnum`)

Ein `Batch` hat keinen Lebenszyklus, sondern einen Typ: `assign` (Verteilung), `notify`
(Benachrichtigung), `ingest`, `remove` und `api`.
