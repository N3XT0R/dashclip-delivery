# Kennzeichen unkenntlich machen

Ein Team kann in seinem Profil unter „Einstellungen“ verlangen, dass Kennzeichen in seinen Videos
verpixelt werden, bevor sie an Kanäle gehen. Die Erkennung läuft auf dem Prozessor, eine Grafikkarte
ist nicht nötig. Diese Anleitung beschreibt, was dafür auf dem Server vorhanden sein muss.

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

In der `.env`:

```dotenv
CENSOR_ENABLED=true
```

Ohne diesen Schalter bleibt die Funktion aus. Schaltet ein Team die Unkenntlichmachung trotzdem ein,
scheitert die Aufbereitung des Videos mit einer klaren Meldung, statt es unbearbeitet auszuliefern.

Weitere Werte, alle optional, mit den Vorgaben aus `config/censor.php`:

| Schlüssel | Vorgabe | Bedeutung |
|-----------|---------|-----------|
| `CENSOR_PYTHON` | `/opt/censor/venv/bin/python` | Pfad zum Python der Laufzeitumgebung. |
| `CENSOR_MODEL` | `/opt/censor/plate.onnx` | Pfad zur Modelldatei. |
| `CENSOR_FRAME_STEP` | `3` | Jedes wievielte Bild geprüft wird. Höher heißt schneller und ungenauer. |
| `CENSOR_CONFIDENCE` | `0.15` | Ab welcher Sicherheit ein Fund gilt. Niedriger: mehr Funde, mehr Fehltreffer. |
| `CENSOR_MARGIN` | `0.25` | Wie weit die Verpixelung über den erkannten Bereich hinausreicht. |
| `CENSOR_TIMEOUT` | `3600` | Nach wie vielen Sekunden ein Lauf abgebrochen wird. |

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

Gemessen auf zwei Kernen, also auf der Ausstattung des Produktionsservers, mit Material in
2560x1440 und 30 Bildern je Sekunde:

| Material | Dauer |
|----------|-------|
| 10 Sekunden | rund 52 Sekunden |
| 3 Minuten | rund 15 Minuten |

Das entspricht etwa dem Fünffachen der Spielzeit. Weil die Verteilung wöchentlich läuft, fällt diese
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
