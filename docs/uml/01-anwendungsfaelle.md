# Anwendungsfälle

Mermaid kennt kein eigenes Anwendungsfalldiagramm, deshalb sind Akteure als Kreise und Anwendungsfälle
als abgerundete Knoten innerhalb der Systemgrenze dargestellt.

```mermaid
flowchart LR
    besucher((Besucher))
    einsender((Einsender))
    kanal((Kanalbetreiber))
    admin((Administrator))
    apiclient((API-Client))
    scheduler((Zeitplaner))

    subgraph plattform[DashClip Delivery]
        uc_lesen([Startseite, Blog und Changelog lesen])
        uc_registrieren([Registrieren und anmelden])
        uc_upload([Clips hochladen])
        uc_webdav([ZIP per WebDAV einliefern])
        uc_status([Verarbeitungsstatus verfolgen])
        uc_antrag([Kanalzugang beantragen])
        uc_angebote([Angebote ansehen])
        uc_download([Videos einzeln oder als ZIP laden])
        uc_rueckgabe([Nicht benötigte Videos zurückgeben])
        uc_kanal([Kanaleinstellungen und Team pflegen])
        uc_verwalten([Benutzer, Kanäle, Videos und Konfiguration verwalten])
        uc_freigeben([Kanalanträge freigeben])
        uc_blog([Blogbeiträge veröffentlichen])
        uc_protokoll([Aktivitäten, Downloads und Mails prüfen])
        uc_verteilen([Videos verteilen und Kanäle benachrichtigen])
        uc_aufraeumen([Abgelaufenes und Gelöschtes aufräumen])
    end

    besucher --- uc_lesen
    besucher --- uc_registrieren
    einsender --- uc_upload
    einsender --- uc_webdav
    einsender --- uc_status
    einsender --- uc_antrag
    kanal --- uc_angebote
    kanal --- uc_download
    kanal --- uc_rueckgabe
    kanal --- uc_kanal
    apiclient --- uc_upload
    apiclient --- uc_angebote
    apiclient --- uc_download
    apiclient --- uc_kanal
    admin --- uc_verwalten
    admin --- uc_freigeben
    admin --- uc_blog
    admin --- uc_protokoll
    scheduler --- uc_verteilen
    scheduler --- uc_aufraeumen

    uc_download -. include .-> uc_angebote
    uc_rueckgabe -. include .-> uc_angebote
    uc_upload -. extend .-> uc_status
```

| Akteur          | Zugang                                                         |
|-----------------|----------------------------------------------------------------|
| Besucher        | Öffentliche Website ohne Anmeldung                             |
| Einsender       | Bereich für Einsender, Rolle `panel_user`                      |
| Kanalbetreiber  | Bereich für Einsender mit Rolle `channel_operator` oder signierter Angebotslink aus der Mail |
| Administrator   | Verwaltungsbereich, Rolle `super_admin`                        |
| API-Client      | REST-API `v1` mit OAuth-Token und Scopes                       |
| Zeitplaner      | Geplante Befehle, siehe [Zeitplan](09-zeitplan.md)             |
