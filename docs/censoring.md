# Kennzeichen unkenntlich machen

Kennzeichen werden in den Videos eines Teams verpixelt, bevor sie an Kanäle gehen. Das ist der
Standard; wer das Team besitzt, kann es im eigenen Profil unter „Einstellungen“ abschalten. Beim
Hochladen steht ein Hinweis darauf. Die Erkennung läuft auf dem Prozessor, eine Grafikkarte ist
nicht nötig. Diese Anleitung beschreibt, was dafür auf dem Server vorhanden sein muss.

Auf einer Installation ohne die unten beschriebenen Werkzeuge greift der Standard nicht: Videos
laufen unverändert durch, statt in der Aufbereitung hängen zu bleiben. Nur wenn ein Team die
Unkenntlichmachung ausdrücklich eingeschaltet hat, scheitert die Aufbereitung, statt unbearbeitet
auszuliefern.

## Was gebraucht wird

| Bestandteil | Zweck |
|-------------|-------|
| `ffmpeg` | Liest und schreibt die Videos, ist für die Vorschauen ohnehin installiert. |
| Python 3 | Führt das Erkennungsskript `resources/scripts/censor_video.py` aus. |
| `onnxruntime` und `numpy` | Rechnen das Modell auf dem Prozessor. |
| Modelldatei | YOLOv8 für Kennzeichen, rund 12 MB, MIT-Lizenz. |

## Mit dem mitgelieferten Abbild

Das Abbild aus `app.Dockerfile` bringt alles mit: Python, eine abgeschottete Laufzeitumgebung unter
`/opt/censor/venv` und das Modell unter `/opt/censor/plate.onnx`. Nach einer Aktualisierung genügt
ein Neubau:

```bash
docker compose build sharing
docker compose up -d sharing
```

Das Abbild wächst dadurch um etwa 250 MB.

## Ohne das Abbild, direkt auf einem Server

```bash
sudo apt-get install -y python3 python3-venv ffmpeg

sudo mkdir -p /opt/censor
sudo python3 -m venv /opt/censor/venv
sudo /opt/censor/venv/bin/pip install --no-cache-dir onnxruntime numpy

sudo curl -sSL -o /opt/censor/plate.onnx \
  https://huggingface.co/ml-debi/yolov8-license-plate-detection/resolve/main/best.onnx

sudo chown -R www-data:www-data /opt/censor
```

Der Benutzer, unter dem die Warteschlange läuft, muss `/opt/censor` lesen dürfen.

## Einschalten

Es braucht zwei Dinge, absichtlich getrennt: die Werkzeuge auf der Maschine und die Freigabe in der
Anwendung.

**1. Auf dem Server**, in der `.env`:

```dotenv
CENSOR_ENABLED=true
```

**2. In der Verwaltung**, unter Einstellungen im Bereich der Unkenntlichmachung den Eintrag
„Kennzeichen unkenntlich machen" auf an stellen. Er steht nach der Installation auf aus, weil die
Verarbeitung eine Maschine je Video für Minuten beschäftigt. So lässt sie sich ohne Deployment
anhalten, etwa wenn der Server für anderes gebraucht wird.

Fehlt eines von beidem, bleibt die Funktion aus. Schaltet ein Team die Unkenntlichmachung trotzdem ein,
scheitert die Aufbereitung des Videos mit einer klaren Meldung, statt es unbearbeitet auszuliefern.

## Erkennung einstellen

In der Administration unter Konfiguration steht die Kategorie „Kennzeichen-Verpixelung“ bereit.
Die dort gespeicherten Werte werden für jedes Video neu gelesen, auch in bereits laufenden
Arbeitern. Eine Änderung wirkt ab dem nächsten Verarbeitungslauf, nicht mitten in einem Video.

| Einstellung | Vorgabe | Zulässiger Bereich |
|-------------|---------|--------------------|
| Kachelspalten | 3 | 1 bis 8 |
| Kachelzeilen | 2 | 1 bis 8 |
| Bildintervall | 3 | 1 bis 60; höher ist schneller, aber ungenauer |
| Erkennungsschwelle | 0,15 | 0,01 bis 1; kleiner erkennt mehr, auch mehr Fehltreffer |
| Zusätzlicher Rand | 0,25 | 0 bis 1; Anteil an der Größe des Fundes |
| Zeitlimit in Sekunden | 3600 | 1 bis 3600 |
| Rechenfäden der Erkennung | 2 | 1 bis 16 |
| Suchbeginn im Bild | 0 | 0 bis 0,9 |
| Randwachstum je Bild | 0,2 | 0 bis 1 |

Der idempotente `CensorConfigSeeder` legt die Kategorie und fehlende Einträge an. Die Migration
ruft ihn beim Deployment auf; er ist auch im regulären `DatabaseSeeder` eingebunden. Vorhandene Werte aus `CENSOR_FRAME_STEP`,
`CENSOR_CONFIDENCE`, `CENSOR_MARGIN` und `CENSOR_TIMEOUT` werden einmalig übernommen. Danach sind die
Datenbankwerte maßgeblich; die alten Umgebungsvariablen ändern die Verarbeitung nicht mehr.
Werte außerhalb der angegebenen Bereiche müssen vor der Migration korrigiert werden.

Zwischen zwei Erkennungen wird ein gefundener Bereich weitergetragen, und was er verdeckt, bewegt
sich in dieser Zeit weiter. Das Randwachstum lässt ihn deshalb mit seinem Alter mitwachsen. Gemessen
an 20 Sekunden Material, bei jedem dritten Bild:

| Rand | Kennzeichen bleibt offen | Kasten sitzt auf Hintergrund | verpixelte Fläche |
|------|--------------------------|------------------------------|-------------------|
| fest 0,25 | 46 | 49 | 0,27 % |
| fest 0,45 | 39 | 48 | 0,40 % |
| fest 0,65 | 35 | 46 | 0,56 % |
| 0,25 mit Wachstum 0,2 | 35 | 47 | 0,41 % |

Das Mitwachsen erreicht dasselbe wie ein fester Rand von 0,65 und verpixelt dabei ein Viertel
weniger Fläche. Gegen Kästen, die auf Hintergrund sitzen, hilft es kaum: das sind überwiegend
Fehltreffer des Modells, und dagegen wirkt nur die Erkennungsschwelle.

Der Suchbeginn legt fest, ab welcher Bildhöhe überhaupt gesucht wird. 0,4 überspringt die oberen
40 Prozent. Wichtig dabei: **allein spart das nichts.** Die Rechenzeit hängt an der Anzahl der
Kacheln, nicht an der durchsuchten Fläche, denn jede Kachel wird ohnehin auf dieselbe Größe
gebracht, bevor das Modell sie ansieht. Wer nur den Suchbeginn anhebt und die Kachelzeilen auf 2
stehen lässt, bekommt gleich lange Laufzeiten und flachere, verzerrte Kacheln, also schlechtere
Erkennung.

Der Gewinn entsteht erst im Paar: **Suchbeginn 0,4 zusammen mit einer Kachelzeile.** Aus sechs
Kacheln werden drei, und die drei sind mit 853x864 nahezu quadratisch, was dem Modell entgegenkommt.
Gemessen an 20 Sekunden Material in 2560x1440 auf zwei Rechenfäden:

| Einstellung | Dauer | Erkennung allein |
|-------------|-------|------------------|
| 3 Spalten, 2 Zeilen, ab 0 (heutige Vorgabe) | 75 s | 1,00x |
| 3 Spalten, 1 Zeile, ab 0,4 | 45 s | 2,06x |
| 3 Spalten, 1 Zeile, ab 0,4, jedes 6. Bild | 30 s | |
| 3 Spalten, 2 Zeilen, ab 0,4 (die Falle) | 73 s | 1,03x |

Die Vorgabe steht deshalb auf 0: es ändert sich nichts, bis jemand das Paar bewusst setzt.

Die Rechenfäden bestimmen, wie viele Kerne die Erkennung selbst belegt. Die Vorgabe 2 passt auf
die kleinste Maschine. Auf einer größeren lohnt es, sie zu erhöhen, aber nicht auf die volle
Kernzahl: das Lesen und Schreiben des Videos läuft daneben und braucht ebenfalls einen Kern. Bei
vier Kernen ist 3 die sinnvolle Einstellung.

Serverabhängig bleiben `CENSOR_ENABLED`, `CENSOR_PYTHON` (Vorgabe `/opt/censor/venv/bin/python`)
und `CENSOR_MODEL` (Vorgabe `/opt/censor/plate.onnx`). Der Skriptpfad bleibt ebenfalls in der Datei.
Die persönlichen Teamentscheidungen werden weiterhin separat gespeichert.

## Prüfen, ob es läuft

Das Skript lässt sich von Hand aufrufen:

```bash
/opt/censor/venv/bin/python resources/scripts/censor_video.py \
  --input /pfad/zum/clip.mp4 \
  --output /tmp/clip_verpixelt.mp4 \
  --model /opt/censor/plate.onnx
```

Am Ende steht eine Zeile wie `{"frames": 100, "regions": 942, "total_frames": 300}`: geprüfte
Bilder, verpixelte Bereiche, Bilder insgesamt. Danach lohnt ein Blick in die Ausgabedatei.

## Was es an Rechenzeit kostet

Gemessen auf zwei Kernen mit zwei Rechenfäden, mit Material in 2560x1440 und 30 Bildern je
Sekunde:

| Material | Dauer |
|----------|-------|
| 10 Sekunden | rund 52 Sekunden |
| 3 Minuten | rund 15 Minuten |

Das entspricht etwa dem Fünffachen der Spielzeit. Mit mehr Rechenfäden auf mehr Kernen sinkt die
Dauer, aber nicht im gleichen Verhältnis: ein Teil der Zeit geht für das Lesen und Neuschreiben des
Videos drauf und lässt sich so nicht verkürzen. Weil die Verteilung wöchentlich läuft, fällt diese
Wartezeit im Ablauf nicht auf, sie belegt aber für die Dauer einen Arbeiter der Warteschlange.

## Wie gut es trifft

Das Bild wird in 3x2 Kacheln geprüft, weil ein Kennzeichen beim Verkleinern des ganzen Bildes auf
die Kantenlänge des Modells auf etwa zehn Pixel schrumpft und dann nicht mehr gefunden wird. Über
neun Stichproben fand das Modell ohne Kacheln 3 Bereiche, mit Kacheln 70.

Getroffen werden die nahen, lesbaren Kennzeichen. Kleine, weit entfernte oder stark abgewinkelte
rutschen durch, gelegentlich wird auch ein Schild mit verpixelt. Es bleibt eine Hilfe, keine
Garantie, und genau so steht es auch am Schalter im Profil.

## Wenn etwas schiefgeht

- Die Aufbereitung eines Videos bleibt im Schritt `censor_video` hängen: Im Anwendungsprotokoll
  stehen der Rückgabewert und die Fehlerausgabe des Skripts.
- Häufigste Ursachen: `CENSOR_ENABLED` nicht gesetzt, Modelldatei fehlt oder ist nicht lesbar,
  Python-Pfad falsch.
- Zum Abschalten genügt `CENSOR_ENABLED=false`. Betroffene Videos bleiben dann unbearbeitet liegen,
  bis die Teams den Schalter selbst ausschalten.
