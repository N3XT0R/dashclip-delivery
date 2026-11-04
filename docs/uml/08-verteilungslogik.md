# Verteilung der Videos

Aktivitätsdiagramm zu `AssignmentDistributor::distribute()`.

```mermaid
flowchart TD
    start([assign:distribute]) --> batch[Batch vom Typ assign starten]
    batch --> pool[Pool sammeln:<br/>neue, nicht zugewiesene und<br/>abgelaufene Videos]
    pool --> empty{Pool leer?}
    empty -- ja --> abort([Batch beenden, nichts zu tun])
    empty -- nein --> split[Pool nach Team oder<br/>Uploader aufteilen]
    split --> each{Nächster<br/>Uploader-Pool?}
    each -- keiner --> finish[Batch mit Statistik beenden] --> ende([Ende])
    each -- ja --> bundles[Bundles vervollständigen]
    bundles --> channels[Kanäle, Rotation und<br/>Wochenquoten vorbereiten]
    channels --> groups[Gruppen bilden:<br/>Videos eines Bundles bleiben zusammen]
    groups --> preload[Vorladen: aktive Sperren,<br/>bereits angebotene Kanäle,<br/>bevorzugter Kanal]
    preload --> group{Nächste Gruppe?}
    group -- keine --> each
    group -- ja --> pref{Bevorzugter Kanal<br/>gesetzt und frei?}
    pref -- ja --> assignpref[Diesem Kanal anbieten<br/>via_preferred_channel]
    pref -- nein --> pick[Kanal nach Gewicht und<br/>Rotation wählen, gesperrte,<br/>pausierte und volle Kanäle auslassen]
    pick --> found{Kanal gefunden?}
    found -- nein --> group
    found -- ja --> assign[Angebot anlegen<br/>oder nächste Runde öffnen]
    assignpref --> assign
    assign --> quota[Quote des Kanals hochzählen]
    quota --> group

    split -. Fehler im Pool .-> warn[Warnung loggen,<br/>Videos als fehlgeschlagen zählen] -.-> each
```

Ein Angebot wird innerhalb einer Transaktion angelegt, die das Video sperrt. Wurde das Video
inzwischen gelöscht, bricht die Anlage mit `VideoUnavailableForOfferException` ab.
