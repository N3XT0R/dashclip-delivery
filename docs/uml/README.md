# UML-Diagramme

Diese Diagramme zeigen, wie die Plattform aufgebaut ist und wie ihre Abläufe zusammenspielen. Sie sind
in [Mermaid](https://mermaid.js.org/) geschrieben und werden auf GitHub und in PhpStorm direkt
gerendert.

| Diagramm                                       | UML-Art                     | Inhalt                                                   |
|------------------------------------------------|-----------------------------|----------------------------------------------------------|
| [Anwendungsfälle](01-anwendungsfaelle.md)      | Anwendungsfalldiagramm      | Akteure und was sie mit der Plattform tun                |
| [Komponenten](02-komponenten.md)               | Komponentendiagramm         | Schichten der Anwendung und externe Systeme              |
| [Verteilung](03-verteilung.md)                 | Verteilungsdiagramm         | Container der lokalen Umgebung und ihre Verbindungen     |
| [Domänenmodell](04-domaenenmodell.md)          | Klassendiagramm             | Modelle und ihre Beziehungen                             |
| [Ingest-Pipeline](05-ingest-pipeline.md)       | Klassen- und Sequenzdiagramm | Schritte vom Upload bis zum fertig verarbeiteten Video   |
| [Zustände](06-zustaende.md)                    | Zustandsdiagramme           | Angebot, Videoverarbeitung, Kanalantrag, ZIP-Erstellung  |
| [Abläufe](07-ablaeufe.md)                      | Sequenzdiagramme            | Upload, Wochenlauf, Angebotsdownload, Export, Kanalantrag, API |
| [Verteilung der Videos](08-verteilungslogik.md) | Aktivitätsdiagramm         | Wie Videos auf Kanäle verteilt werden                    |
| [Zeitplan](09-zeitplan.md)                     | Zeitverlaufsübersicht       | Geplante Befehle und ihre Taktung                        |

Die Diagramme beschreiben den Stand des Codes zum Zeitpunkt ihrer Erstellung. Bei Abweichungen gilt der
Code.
