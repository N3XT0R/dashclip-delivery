# Deployment mit Deployer

Diese Anleitung beschreibt den Einsatz von [Deployer](https://deployer.org) zum automatischen Ausrollen der Anwendung.

## Vorbereitung

1. Beispieldatei kopieren und anpassen:
   ```bash
   cp deploy.php.dist deploy.php
   ```
2. In `deploy.php` den GitHub Personal Access Token (`GITHUB_PAT`) und die Ziel-Hosts eintragen.

## Deployment ausführen

Die Datei `deployer.phar` liegt im Projektverzeichnis. Ein Deployment wird mit folgendem Befehl gestartet:

```bash
php deployer.phar deploy prd
```

Dabei bezeichnet `prd` den in `deploy.php` definierten Host. Weitere Details bietet die [Dokumentation von Deployer](https://deployer.org/docs).

## Erstes Deployment des Blogs

Die regulären Migrationen legen die Blogtabellen an. Anschließend müssen einmalig die
Redaktionsrechte registriert werden, ohne andere Rollen oder Rechte zurückzusetzen:

```bash
php artisan db:seed --class=BlogPermissionSeeder --force
```

Der Seeder ist wiederholbar und ergänzt die Rechte für `super_admin`. Weitere Redaktionsrollen
können anschließend im Adminbereich die benötigten Blogrechte erhalten.

Der bestehende Laravel-Scheduler veröffentlicht fällige Beiträge über
`blog:publish-scheduled` einmal pro Minute. Der öffentliche Storage-Link muss auf den
gemeinsam genutzten Storage zeigen, damit hochgeladene Beitragsbilder Releasewechsel überstehen.
Der bestehende Asset-Build bleibt erforderlich; zusätzliche Pakete oder Umgebungsvariablen
werden für den Blog nicht benötigt. `/sitemap.xml` enthält die öffentlichen Seiten und
veröffentlichten Blog-Inhalte auf Deutsch und Englisch. Die dynamische `/robots.txt` verweist
mit einer absoluten URL darauf; `/blog-sitemap.xml` bleibt als Blog-Teilausgabe verfügbar.
Die RSS-Feeds liegen unter `/blog/feed.xml` und `/en/blog/feed.xml`.

`robots.txt` und `sitemap.xml` müssen durch Laravel ausgeliefert werden. Eine alte statische
`public/robots.txt` darf die Route nicht überdecken; sie ist deshalb nicht mehr Teil des Projekts.
Artikeländerungen erscheinen unmittelbar in der Sitemap. Entwürfe, geplante, zurückgezogene
und nicht indexierbare Artikel sowie Artikel mit einer abweichenden kanonischen URL fehlen.
Für Artikel wird `lastmod` aus den gespeicherten Änderungsdaten ermittelt, nicht aus dem Abrufdatum.

### Initiale Blog-Inhalte

Nach den Migrationen kann der erste redaktionelle Inhalt gezielt angelegt werden:

```bash
php artisan db:seed --class=BlogContentSeeder --force
```

Dieser eigenständig aufgerufene Seeder legt drei Kategorien, fünf Tags und einen vollständigen,
veröffentlichten Einführungsartikel auf Deutsch und Englisch an. Als Autor wird der vorhandene
Benutzer mit der kleinsten ID verwendet, der die Rolle `super_admin` im Guard `web` besitzt.
Fehlt ein solcher Benutzer, bricht der Seeder ohne Änderungen ab.

Die Tabelle `blog_seed_runs` hält den erfolgreichen Erstlauf dauerhaft fest. Weitere Aufrufe
verändern nichts, auch wenn Titel oder Slugs inzwischen geändert oder Inhalte gelöscht wurden.
Bereits vorhandene Kategorien und Tags mit den initialen Slugs werden beim Erstlauf verwendet;
ihre vorhandenen Übersetzungen bleiben erhalten. Inhalt und Ausführungsmarkierung werden in
derselben Transaktion gespeichert: Bei einem Fehler bleibt kein halbfertiger Erstlauf zurück.

### Release-Neuigkeiten

Zu jeder neuen Version erscheint ein Neuigkeiten-Artikel auf Deutsch und Englisch, der die
Änderungen für Einsender und Kanalbetreiber verständlich zusammenfasst. Weil ein Deployment nur
die Migrationen ausführt, veröffentlicht eine Migration pro Version den Artikel über den
`ReleaseNewsSeeder`. Der Artikel ist damit direkt nach dem Deployment öffentlich; ein
zusätzlicher Befehl ist nicht nötig.

Die Inhalte liegen unter `database/seeders/data/releases/`: `<version>.php` enthält Slugs, Titel,
Kurzbeschreibungen und Metadaten, `<version>.de.md` und `<version>.en.md` die Artikeltexte. Das
Titelbild entsteht mit `node database/seeders/data/images/build-covers.mjs release-<version>`,
wobei die Punkte der Versionsnummer durch Bindestriche ersetzt werden (etwa `release-4-9-0`).

Auch hier verhindert `blog_seed_runs` eine doppelte Veröffentlichung (`release-news-<version>`).
Gibt es noch keinen Benutzer mit der Rolle `super_admin` im Guard `web`, überspringt die
Migration den Artikel, ohne das Deployment abzubrechen; ein späterer Aufruf von
`ReleaseNewsSeeder` mit derselben Version holt ihn nach.

### Speicher bei Hetzner (SFTP)

Die Disk `hetzner` spricht eine Hetzner Storage Box über SFTP an. Die Zugangsdaten kommen aus der
`.env` (siehe `.env.example`): `HETZNER_SFTP_HOST`, `HETZNER_SFTP_PORT` (bei Storage Boxen `23`),
`HETZNER_SFTP_USERNAME` sowie entweder `HETZNER_SFTP_PASSWORD` oder `HETZNER_SFTP_PRIVATE_KEY`
(Pfad oder Inhalt, optional mit `HETZNER_SFTP_PASSPHRASE`). `HETZNER_SFTP_ROOT` legt das
Basisverzeichnis fest. `HETZNER_SFTP_HOST_FINGERPRINT` sollte gesetzt werden, damit die Verbindung
nur zum echten Server aufgebaut wird; der Wert kann im OpenSSH-Format übernommen werden:

```bash
ssh-keyscan -p 23 <host> 2>/dev/null | ssh-keygen -lf -
# Ausgabe z. B.: 256 SHA256:AbCd... <host> (ED25519) -> Wert: SHA256:AbCd...
```

Umzug bestehender Videos von Dropbox:

1. Zugangsdaten setzen und deployen.
2. `php artisan storage:migrate-videos dropbox hetzner --dry-run` zeigt, was kopiert würde.
3. `php artisan storage:migrate-videos dropbox hetzner` kopiert jedes Video an denselben Pfad,
   prüft die Dateigröße und stellt das Video erst danach auf `hetzner` um. Der Lauf kann bei vielen
   Daten lange dauern (in `screen` oder `tmux` starten), lässt sich abbrechen und erneut starten:
   vollständige Kopien werden wiederverwendet. Mit `--video=<id>` und `--limit=<n>` lassen sich
   einzelne Videos oder Teilmengen migrieren. Fehlgeschlagene Videos bleiben auf Dropbox und stehen
   im Log.
4. Die Einstellung `default_file_system` im Adminbereich auf `hetzner` setzen, damit neue Uploads
   dort landen. Nur konfigurierte, nicht lokale Disks werden als Ziel verwendet; ein lokaler Wert
   lässt neue Videos dort, wo sie hochgeladen wurden.

Die Dateien auf Dropbox werden nicht gelöscht; das ist ein eigener Schritt, sobald der neue Speicher
sich bewährt hat.

Hinweis zu den Abhängigkeiten: Der SFTP-Adapter benötigt phpseclib 3. Die OAuth-Bibliothek ist
deshalb auf die Versionsreihe 13.7 festgelegt (`~13.7.3`), deren bekannte Sicherheitslücke ab
13.7.1 behoben ist. Sobald der SFTP-Adapter phpseclib 4 unterstützt, kann die Festlegung entfallen.
