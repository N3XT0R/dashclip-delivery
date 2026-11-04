# Verteilung

Das Diagramm zeigt die Container aus `docker-compose.yml` und ihre Verbindungen. Die Produktion wird per
Deployer ausgerollt (siehe [Deployment](../deployment.md)) und besteht aus denselben Rollen.

```mermaid
flowchart LR
    browser[Browser]
    apiclient[API-Client]
    traefik[Traefik<br/>Reverse Proxy]

    subgraph host[Docker-Host]
        web_sharing[web_sharing<br/>nginx]
        sharing[sharing<br/>PHP-FPM, Artisan, Scheduler]
        horizon[horizon<br/>Queue-Worker]
        reverb[reverb<br/>WebSocket-Server]
        db_sharing[(db_sharing<br/>MariaDB 10.11)]
        redis[(redis)]
        mailhog[mailhog<br/>Test-Mailserver]
        files[(storage/<br/>Videos, Vorschauen, ZIPs)]
    end

    dropbox[(Dropbox)]
    webdav[(WebDAV-Eingang)]
    youtube[YouTube-Einbettung<br/>youtube-nocookie.com]

    browser --> traefik
    apiclient --> traefik
    traefik --> web_sharing
    traefik --> reverb
    web_sharing -- FastCGI --> sharing
    sharing --> db_sharing
    sharing --> redis
    sharing --> files
    sharing -- SMTP --> mailhog
    horizon --> redis
    horizon --> db_sharing
    horizon --> files
    horizon --> dropbox
    horizon -- Ereignisse --> reverb
    sharing --> webdav
    browser -. nach Klick .-> youtube
```

| Container     | Aufgabe                                                                  |
|---------------|--------------------------------------------------------------------------|
| `web_sharing` | Liefert statische Dateien aus `public/` und leitet PHP an `sharing` weiter |
| `sharing`     | Anwendung, Artisan-Befehle, Tests                                        |
| `horizon`     | Arbeitet die Warteschlangen ab: Ingest, ZIP-Bau, Exporte, Mails          |
| `reverb`      | Schickt ZIP-Fortschritt und Benachrichtigungen live an den Browser       |
| `db_sharing`  | Datenbank                                                                |
| `redis`       | Cache, Warteschlangen, Sessions, Download-Fortschritt                    |
| `mailhog`     | Fängt lokal alle ausgehenden Mails ab                                    |
