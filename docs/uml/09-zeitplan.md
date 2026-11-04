# Zeitplan

Geplante Befehle aus `routes/console.php`.

```mermaid
gantt
    title Tagesablauf der geplanten Befehle
    dateFormat HH:mm
    axisFormat %H:%M

    section Täglich
    Datenbank aufräumen            :02:00, 15m
    Abgelaufene Angebote beenden   :03:00, 15m
    Verteilte Videos markieren     :03:30, 15m
    Gelöschte Videos bereinigen    :04:00, 15m
    Inaktive Benutzer erinnern     :09:00, 15m

    section Montags
    weekly:run                     :08:00, 30m
```

| Takt              | Befehle                                                                           |
|-------------------|-----------------------------------------------------------------------------------|
| jede Minute       | Geplante Blogbeiträge veröffentlichen                                             |
| alle 5 Minuten    | Horizon-Snapshot                                                                  |
| alle 10 Minuten   | Uploader zuordnen, Mail-Antworten einlesen                                        |
| alle 15 Minuten   | Videos Teams zuordnen, WebDAV einlesen, hängende, fehlgeschlagene und nie gestartete Verarbeitungen neu einstellen |
| stündlich         | FFmpeg-Temp, abgelaufene ZIPs und Nicht-ZIP-Dateien im WebDAV aufräumen           |
| täglich           | Fehlende Ingest-Schritte neu einstellen, Dropbox-Token erneuern und die Zeiten oben |
| montags 08:00     | `weekly:run`: assign:uploader, assign:expire, assign:distribute, videos:mark-distributed, notify:offers |

```mermaid
flowchart LR
    weekly([weekly:run]) --> u[assign:uploader] --> x[assign:expire] --> d[assign:distribute] --> m[videos:mark-distributed] --> n[notify:offers]
```
