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
werden für den Blog nicht benötigt. `/blog-sitemap.xml` kann bei Suchmaschinen als Sitemap
hinterlegt werden; die RSS-Feeds liegen unter `/blog/feed.xml` und `/en/blog/feed.xml`.

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
