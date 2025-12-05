@php use App\Facades\Cfg; @endphp
@extends('layouts.app')

@section('title', 'Nutzungsbedingungen')

@section('content')
    <div class="panel">
        <h1 class="text-2xl font-bold mb-4">Nutzungsbedingungen</h1>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Geltungsbereich</h2>
            <p>Diese Nutzungsbedingungen regeln die Nutzung des Online-Dienstes
                <strong>{{ config('app.name') }}</strong>, einer Multi-Channel-Verteilungsplattform für
                Videoinhalte (Dashcam-Videos und nutzergenerierten Inhalt).
                Mit dem Aufrufen, Verwenden oder sonstigen Zugriff auf diesen Dienst einschließlich der öffentlichen
                Angebotsseite und des benutzerspezifischen Portals erklären Sie sich mit diesen
                Bedingungen einverstanden. Wenn Sie diesen Bedingungen nicht zustimmen, dürfen Sie den Dienst nicht
                nutzen.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Leistungsbeschreibung</h2>
            <p>{{ config('app.name') }} stellt eine verteilte Plattform bereit, auf der:</p>
            <ul class="list-disc list-inside mb-4">
                <li><strong>Uploader</strong> (Submitter) Videoinhalte hochladen und verwalten können,</li>
                <li><strong>Kanäle</strong> (Channel-Betreiber) Videoinhalte über geschützte Angebotsseiten abrufen
                    können,
                </li>
                <li>Eine automatisierte, faire Verteilungslogik Videos auf Basis von Quoten und Verfügbarkeit
                    zuordnet.
                </li>
            </ul>
            <p>Ein Anspruch auf dauerhafte Verfügbarkeit oder fehlerfreien Betrieb des Dienstes besteht nicht.
                Änderungen, Erweiterungen oder Einschränkungen des Angebots können jederzeit ohne vorherige Ankündigung
                erfolgen.</p>
        </section>
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Kostenfreie und optionale Zusatzfunktionen</h2>
            <p>
                {{ config('app.name') }} stellt derzeit ausschließlich <strong>kostenfreie Funktionen</strong> bereit.
                Zukünftig können optionale <strong>zusätzliche Funktionen</strong> eingeführt werden, für die ein
                Beitrag
                zur Deckung notwendiger Infrastruktur- oder Betriebskosten erhoben wird
                (z.&nbsp;B. zusätzlicher Speicherplatz, höhere Verarbeitungskapazitäten oder andere
                ressourcenintensive Dienste). Eine Gewinnerzielungsabsicht besteht nicht.
            </p>
            <p>
                Solche Zusatzfunktionen sind vollständig optional und für die Nutzung des Grundsystems nicht
                erforderlich.
                Sie können nur aktiviert werden, wenn der Nutzer sie bewusst auswählt und einer entsprechenden
                Kostenbeteiligung
                ausdrücklich zustimmt.
            </p>
            <p>
                Vor Aktivierung einer kostenpflichtigen Zusatzfunktion werden sämtliche
                Informationen – einschließlich Leistungsumfang, Kostenbeitrag und Abrechnungsmodalitäten –
                klar und transparent dargestellt. Zusatzfunktionen können jederzeit wieder deaktiviert werden,
                sofern nicht im Einzelfall abweichende Bedingungen bei Aktivierung kommuniziert wurden.
            </p>
        </section>
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Rollen und Verantwortlichkeiten</h2>
            <p><strong>Uploader/Submitter:</strong> Personen, die Videoinhalte zur Plattform hinzufügen.
                Sie sind allein verantwortlich für die Rechtmäßigkeit, Authentizität und Qualität ihrer Uploads.
                Sie gewährleisten, dass sie alle notwendigen Rechte an den hochgeladenen Inhalten besitzen.</p>
            <p><strong>Kanäle/Channels:</strong> Betreiber von Video-Kanälen oder Plattformen, die Videos aus dem System
                abrufen
                und verwenden dürfen.</p>
            <p><strong>Teams:</strong> Organisatorische Einheiten, die Uploader gruppieren und deren
                Verteilungseinstellungen
                (z.B. Kanal-Auswahl, Quoten) verwalten.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Zulässige Nutzung</h2>
            <p>Die bereitgestellten Inhalte dürfen ausschließlich im Rahmen der vorgesehenen Nutzung verwendet
                werden:</p>
            <ul class="list-disc list-inside mb-4">
                <li><strong>Uploader:</strong> dürfen Videos nur über die vorgesehenen Upload-Funktionen hochladen
                    und ihre Verteilungspools (Team-Kanäle) selbst verwalten.
                </li>
                <li><strong>Kanäle:</strong> erhalten ein einfaches, nicht übertragbares Nutzungsrecht zum Download
                    und zur Veröffentlichung der zugewiesenen Videos im Rahmen ihrer redaktionellen oder
                    plattformbezogenen Tätigkeit.
                    Eine Weitergabe an Dritte, Vervielfältigung über den vorgesehenen Zweck hinaus oder öffentliche
                    Verbreitung ohne ausdrückliche schriftliche Zustimmung des Rechteinhabers ist untersagt.
                </li>
                <li><strong>Automatisierte Prozesse:</strong> Scraping, Bots oder andere automatisierte Zugriffe
                    sind ohne vorherige schriftliche Genehmigung untersagt. Die geplante öffentliche API muss für
                    externe Integrationen verwendet werden.
                </li>
            </ul>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Upload und Inhaltsverantwortung</h2>
            <p>Uploader verpflichten sich:</p>
            <ul class="list-disc list-inside">
                <li>Nur eigene, rechtmäßig erworbene oder frei verwendbare Inhalte hochzuladen,</li>
                <li>Alle erforderlichen Rechte (z.B. Persönlichkeitsrechte, Urheberrechte) an den Inhalten zu besitzen
                    oder lizenziert zu haben,
                </li>
                <li>Die Plattform nicht zur Verbreitung von illegalem, belästigendem, diffamierendem oder anderem
                    schädlichem Inhalt zu nutzen,
                </li>
                <li>Die Nutzungsbedingungen und Datenschutzerklärung zur Kenntnis zu nehmen und einzuhalten.</li>
            </ul>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Verwaltung von Teams und Kanälen</h2>
            <p>Jeder Uploader erhält automatisch ein persönliches Team bei Registrierung.
                Teams ermöglichen es Uploadern, ihre Kanäle selbst zu verwalten:
                Uploader können über die Self-Service-Oberfläche auswählen, an welche Kanäle ihre Videos verteilt werden
                sollen,
                und können Kanäle jederzeit hinzufügen oder entfernen.
                Team-spezifische Quoten können für individuelle Kanal-Vereinbarungen festgelegt werden.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Verteilungslogik und Fairness</h2>
            <p>Videos werden mithilfe eines automatisierten Algorithmus basierend auf Weighted Round-Robin,
                Kanalpräferenzen des Uploaders und konfigurierbaren Quoten verteilt.
                Der System-Betreiber behält sich das Recht vor, die Verteilungslogik jederzeit anzupassen.
                Kanäle können Angebote ablehnen oder zurückweisen; die Plattform versucht dann, das Video an andere
                Kanäle zu verteilen.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Zugangssicherheit und Pflichten</h2>
            <ul class="list-disc list-inside">
                <li>Uploader und Kanäle verpflichten sich, Zugangsdaten vertraulich zu behandeln und nicht an Dritte
                    weiterzugeben.
                </li>
                <li>Für alle Aktivitäten unter Ihrem Konto sind Sie verantwortlich.</li>
                <li>Signierte Offer-Links sind zeitlich begrenzt und persönlich. Eine Weitergabe an unbefugte Dritte ist
                    untersagt.
                </li>
                <li>Bei Verdacht auf Missbrauch, unbefugten Zugriff oder Sicherheitsverletzung ist der Betreiber
                    unverzüglich zu informieren.
                </li>
            </ul>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Urheberrechte und geistiges Eigentum</h2>
            <p>
                Alle Inhalte der Plattform selbst (insbesondere Benutzeroberfläche, Logos, Layouts, Systemfunktionen
                und Dokumentation) unterliegen dem Urheberrecht und sind Eigentum des Betreibers oder seiner
                Lizenzgeber.
            </p>
            <p>
                Der Quellcode von {{ config('app.name') }} wird im Rahmen eines
                <strong>Dual-Lizenzmodells</strong> bereitgestellt:
                einerseits unter der <strong>AGPL-3.0-or-later</strong>, andererseits unter einer
                <strong>kommerziellen Lizenz</strong>.
                Die Nutzung des Quellcodes richtet sich nach den Bedingungen der jeweils gewählten Lizenz.
                Die AGPL-3.0 verpflichtet insbesondere zur Offenlegung von Änderungen und zur Bereitstellung des
                vollständigen
                Quellcodes gegenüber allen Nutzern des angebotenen Dienstes. Die kommerzielle Lizenz erlaubt hingegen
                proprietäre Nutzung ohne Offenlegungspflichten.
            </p>
            <p>
                <strong>Videoinhalte:</strong>
                Mit dem Upload von Videoinhalten räumt der Uploader dem Betreiber – sowie den zugehörigen Kanälen – das
                Recht ein,
                diese Videos zum Zwecke der Verteilung, Speicherung, Vorschau-Generierung, Verwaltung und Nutzung
                innerhalb der
                jeweiligen Kanäle zu verarbeiten. Der Uploader garantiert, dass er über alle hierfür erforderlichen
                Rechte verfügt
                oder entsprechende Erlaubnisse eingeholt hat. Die inhaltliche Verantwortung für die Rechtmäßigkeit und
                Originalität der hochgeladenen Inhalte verbleibt vollständig beim Uploader.
            </p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Audit-Logging und Transparenz</h2>
            <p>{{ config('app.name') }} protokolliert alle relevanten Aktivitäten (Uploads, Downloads,
                Ablehnungen, Abläufe, Benachrichtigungen) für Transparenz, Compliance und Audit-Zwecke.
                Uploader und Kanäle können ihre Aktivitätshistorie und den Status ihrer Videos/Angebote
                jederzeit über die Plattform einsehen.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Haftungsausschluss</h2>
            <p>Der Betreiber übernimmt keine Gewähr für:</p>
            <ul class="list-disc list-inside mb-4">
                <li>Die Richtigkeit, Vollständigkeit oder Aktualität der bereitgestellten Inhalte,</li>
                <li>Die Verfügbarkeit oder Fehlerfreiheit des Dienstes,</li>
                <li>Die Einhaltung aller Gesetze durch Uploader oder Kanäle.</li>
            </ul>
            <p>Jegliche Haftung für Schäden, die aus der Nutzung, Nichtverfügbarkeit oder Fehlern des Dienstes
                entstehen,
                ist ausgeschlossen, soweit gesetzlich zulässig. Dies gilt insbesondere für Datenverluste, Umsatzeinbußen
                oder entgangene Gewinne.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Datenschutz</h2>
            <p>Informationen zur Verarbeitung personenbezogener Daten, einschließlich Cookie-Nutzung,
                Video-Metadaten-Speicherung und Download-Tracking, finden Sie in der
                <a href="{{ route('datenschutz') }}">Datenschutzerklärung</a>.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Kündigung und Deaktivierung</h2>
            <p>Der Betreiber behält sich das Recht vor, Konten oder den Zugang zur Plattform
                jederzeit und ohne vorherige Ankündigung zu sperren oder zu löschen, falls:</p>
            <ul class="list-disc list-inside">
                <li>Diese Nutzungsbedingungen verletzt werden,</li>
                <li>Illegale, missbräuchliche oder schädliche Aktivitäten stattfinden,</li>
                <li>Der Dienst aus technischen, rechtlichen oder geschäftlichen Gründen eingestellt wird.</li>
            </ul>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Einwilligung zur Nutzung</h2>
            <p>Mit der Nutzung des Dienstes erklären Sie sich ausdrücklich mit diesen Nutzungsbedingungen
                sowie der <a href="{{ route('datenschutz') }}">Datenschutzerklärung</a> einverstanden.
                Die Einwilligung erfolgt konkludent durch Nutzung des Angebots und kann jederzeit durch
                Beendigung der Nutzung und Kontolöschung widerrufen werden.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Kontakt</h2>
            <p>Fragen, Bedenken oder Meldungen zu diesen Nutzungsbedingungen, Missbrauch oder technischen Problemen
                können Sie jederzeit per E-Mail an
                <a href="mailto:{{ Cfg::get('email_admin_mail', 'email') }}">{{ Cfg::get('email_admin_mail', 'email') }}</a>
                richten.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold mb-2">Schlussbestimmungen</h2>
            <p>Es gilt das Recht der Bundesrepublik Deutschland. Sollten einzelne Bestimmungen dieser
                Nutzungsbedingungen unwirksam sein, bleibt die Gültigkeit der übrigen Bestimmungen unberührt.
                Diese Nutzungsbedingungen können jederzeit ohne vorherige Ankündigung geändert werden.
                Fortgesetzte Nutzung nach Änderungen bedeutet Akzeptanz der neuen Bedingungen.</p>
        </section>
    </div>
@endsection