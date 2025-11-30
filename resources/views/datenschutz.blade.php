@php use App\Facades\Cfg; @endphp
@extends('layouts.app')

@section('title', 'Datenschutz')

@section('content')
    <div class="panel">
        <h1 class="text-2xl font-bold mb-4">Datenschutzerklärung</h1>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Verantwortlicher</h2>
            <p>Verantwortlich für die Datenverarbeitung ist die im <a href="{{ route('impressum') }}">Impressum</a>
                genannte Person.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Allgemeines</h2>
            <p>Der Schutz Ihrer personenbezogenen Daten wird ernst genommen. Die nachfolgenden Hinweise geben einen
                Überblick darüber, welche Daten zu welchem Zweck erhoben werden und was mit Ihren Daten passiert.
                {{ config('app.name') }} ist eine Multi-Channel-Verteilungsplattform für Videoinhalte und verarbeitet
                Daten von Uploadern, Kanälen und Besuchern unterschiedlich.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Benutzerkonten und Authentifizierung</h2>
            <p><strong>Uploader und Kanäle:</strong> Bei der Registrierung und Nutzung des benutzerspezifischen Portals
                werden folgende Daten erhoben und gespeichert:</p>
            <ul class="list-disc list-inside mb-4">
                <li>E-Mail-Adresse</li>
                <li>Benutzername / Display-Name</li>
                <li>Verschlüsseltes Passwort</li>
                <li>Multi-Faktor-Authentifizierung (MFA) Daten (TOTP-Geheimnisse, E-Mail-Codes)</li>
                <li>Registrierungsdatum und letzte Anmeldezeit</li>
                <li>Team-Zugehörigkeit</li>
            </ul>
            <p>Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. a DSGVO (Einwilligung) und dient der
                Verwaltung von Konten, Sicherheit und Zugriffskontrolle.
                MFA-Daten werden ausschließlich lokal gespeichert und nie an Dritte übermittelt.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Video-Uploads und Metadaten</h2>
            <p><strong>Uploader:</strong> Bei der Verwendung der Upload-Funktion werden folgende Daten erfasst:</p>
            <ul class="list-disc list-inside mb-4">
                <li>Videodatei und -metadaten (Titel, Duration, Dateiname)</li>
                <li>SHA-256 Hash (zur Deduplizierung)</li>
                <li>Zeitstempel des Uploads</li>
                <li>Benutzerkennungen und Team-Zuordnung des Uploaders</li>
                <li>Video-Vorschau (automatisch generierter komprimierter MP4)</li>
            </ul>
            <p>Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. a DSGVO (Einwilligung durch Upload)
                und dient der Verwaltung, Verteilung und Deduplizierung von Inhalten.
                Videos werden im konfigurierten Speicher-Backend (lokal, Dropbox, S3) gespeichert.
                Der SHA-256 Hash ermöglicht eine sichere, inhaltsadressierte Speicherung.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Verteilung und Angebots-Verwaltung</h2>
            <p>Bei der automatisierten Verteilung von Videos an Kanäle werden folgende Daten verarbeitet:</p>
            <ul class="list-disc list-inside mb-4">
                <li>Angebots-Zuordnungen (Assignment: Video ↔ Kanal)</li>
                <li>Angebots-Status (ausstehend, akzeptiert, abgelehnt, abgelaufen, zurückgegeben)</li>
                <li>Benachrichtigungsversand und -empfang</li>
                <li>Signierte, zeitlich begrenzte Offer-Links mit Zufalls-Tokens</li>
                <li>Download-Tracking: Kanal-Name, Download-Datum, Download-Zeitpunkt</li>
                <li>Return-Gründe und -Metadaten</li>
            </ul>
            <p>Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. a DSGVO (Einwilligung)
                und dient der fairen, automatisierten Verteilung und der Audit-Compliance.
                Offer-Links sind signiert (Laravel URL Signing) und verlieren ihre Gültigkeit nach dem eingestellten
                Ablaufdatum (konfigurierbar).</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Benachrichtigungen und E-Mail-Kommunikation</h2>
            <p>Die Plattform sendet automatisierte E-Mails in folgenden Fällen:</p>
            <ul class="list-disc list-inside mb-4">
                <li>Willkommens-E-Mails bei Registrierung oder Kanal-Erstellung</li>
                <li>Bestätigungs-E-Mails für E-Mail-Änderungen und Team-Genehmigungen</li>
                <li>Angebots-Benachrichtigungen (ein Kanal erhält ein Video-Angebot)</li>
                <li>Erinnerungs-E-Mails vor Angebots-Ablauf</li>
                <li>Upload-Verarbeitungs-Benachrichtigungen (Erfolg oder Duplikat erkannt)</li>
            </ul>
            <p>Alle gesendeten und empfangenen E-Mails werden in der Plattform geloggt und gespeichert
                (MailLog: Empfänger, Betreff, Zeitstempel, Status).
                Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. a DSGVO (Einwilligung durch Nutzung)
                und ist notwendig für die Funktionalität der Plattform.
                In Entwicklungs- und Test-Umgebungen können E-Mails über einen Catch-All-Mechanismus
                zu einer Testadresse weitergeleitet werden.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Server-Log-Dateien und technische Daten</h2>
            <p>Beim Besuch der Website und bei API-Anfragen werden automatisch Informationen in Server-Log-Dateien
                gespeichert. Dies sind:</p>
            <ul class="list-disc list-inside mb-4">
                <li>IP-Adresse des zugreifenden Geräts</li>
                <li>Datum und Uhrzeit der Anfrage</li>
                <li>URL der abgerufenen Datei / des API-Endpunkts</li>
                <li>HTTP-Methode und Response-Code</li>
                <li>Referrer-URL</li>
                <li>Browsertyp und -version</li>
                <li>User-Agent</li>
            </ul>
            <p>Die Speicherung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO und dient der Sicherstellung eines
                störungsfreien Betriebs, der Sicherheit und der Debugging.
                Server-Log-Daten werden regelmäßig gelöscht, sobald sie für den Zweck der Erhebung nicht mehr benötigt
                werden (typischerweise nach 30 Tagen).</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Activity Logging und Audit Trail</h2>
            <p>Alle wichtigen Aktivitäten auf der Plattform werden protokolliert (Activity Log):
                Benutzeraktionen (Login, Dateiuploads, Kanal-Auswahl, Angebots-Verwerfung),
                Systemereignisse (automatisierte Verteilungen, Ablauf von Angeboten, Batch-Prozesse)
                und administrative Änderungen (Konfigurationsänderungen, Rollen-Zuweisungen).
                Jeder Log-Eintrag enthält einen Zeitstempel, die verantwortliche Person (Causer),
                die betroffene Ressource und die durchgeführte Aktion.
                Diese Daten dienen der Transparenz, Compliance, Debugging und dem Schutz vor Missbrauch.
                Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse)
                und Art. 32 DSGVO (Sicherheitsmaßnahmen).</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Cookies und lokale Speicherung</h2>
            <p>Diese Website und die Portale verwenden ausschließlich technisch notwendige Cookies:
                Ein Session-Cookie (<code>laravel_session</code>) und ein Sicherheits-Cookie (<code>XSRF-TOKEN</code>),
                die für die Bereitstellung und Sicherheit erforderlich sind und nach Ende Ihrer Sitzung gelöscht werden.
                Darüber hinaus wird Ihre Theme-Einstellung im lokalen Speicher Ihres Browsers gespeichert.
                Eine Analyse oder Tracking durch Drittanbieter findet nicht statt.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Dropbox-Integration</h2>
            <p>Wenn Sie Dropbox mit Ihrem Konto verbinden, wird das OAuth-Token gespeichert und zur
                Authentifizierung bei Dropbox verwendet. Tokens werden auf Anfrage aktualisiert
                und sind mit Ihrem Benutzerkonto verknüpft.
                Dropbox verarbeitet ebenfalls Daten nach deren <a href="https://www.dropbox.com/privacy"
                                                                  target="_blank">Datenschutzerklärung</a>.
                Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. a DSGVO (Einwilligung).</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Download-Tracking</h2>
            <p>Kanäle, die Videos über signierte Links abrufen, werden statistisch erfasst:
                Kanal-Name, Video-ID, Download-Zeitpunkt, User-Agent des Geräts.
                Diese Daten ermöglichen Uploadern und Administratoren, nachzuvollziehen,
                welche Kanäle ihre Videos verwendet haben.
                Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. a DSGVO (Einwilligung durch Download).</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Speicherdauer</h2>
            <p><strong>Benutzerkonten:</strong> Solange das Konto aktiv ist oder bis zur Löschung auf Anfrage.</p>
            <p><strong>Videoinhalte:</strong> Solange sie auf der Plattform verwaltet werden.
                Abgelaufene oder gelöschte Videos und deren Vorschau-Dateien werden aus dem Speicher entfernt.</p>
            <p><strong>Angebots- und Verteilungsdaten:</strong> Zur Audit-Compliance unbegrenzt gespeichert.
                Persönlich identifizierbare Informationen werden nach Bedarf pseudonymisiert.</p>
            <p><strong>E-Mail-Logs:</strong> Unbegrenzt für Audit-Zwecke.</p>
            <p><strong>Activity Logs:</strong> Unbegrenzt für Audit- und Compliance-Zwecke.</p>
            <p><strong>Server-Logs:</strong> Regelmäßig gelöscht nach typischerweise 30 Tagen.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Datensicherheit</h2>
            <p>Die Plattform implementiert folgende Sicherheitsmaßnahmen (Art. 32 DSGVO):</p>
            <ul class="list-disc list-inside mb-4">
                <li>HTTPS-Verschlüsselung für alle Datenübertragungen</li>
                <li>Verschlüsselte Speicherung von Passwörtern (bcrypt)</li>
                <li>Multi-Faktor-Authentifizierung (MFA) zur Kontosicherheit</li>
                <li>Signierte, zeitlich begrenzte Tokens für Offer-Links</li>
                <li>Granulare Zugriffskontrolle und rollenbasierte Berechtigungen (Filament Shield)</li>
                <li>Activity Logging für Anomalie-Erkennung und Audit-Compliance</li>
                <li>Regelmäßige Backups (Konfiguration des Betreibers)</li>
            </ul>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Weitergabe an Dritte</h2>
            <p>Ihre personenbezogenen Daten werden nicht an Dritte weitergegeben, außer wenn:</p>
            <ul class="list-disc list-inside mb-4">
                <li>Sie explizit zugestimmt haben (z.B. Dropbox-Integration),</li>
                <li>Dies rechtlich erforderlich ist (z.B. auf Gerichtsbeschluss),</li>
                <li>Dies zur Erbringung der Dienstleistung notwendig ist (z.B. E-Mail-Versand über vertrauenswürdige
                    Provider).
                </li>
            </ul>
            <p>Videoinhalte werden ausschließlich an die von Ihnen ausgewählten Kanäle verteilt.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Ihre Rechte (Art. 15–22 DSGVO)</h2>
            <p>Sie haben folgende Rechte bezüglich Ihrer personenbezogenen Daten:</p>
            <ul class="list-disc list-inside mb-4">
                <li><strong>Auskunftspflicht (Art. 15):</strong> Sie können erfragen, welche Daten verarbeitet werden.
                </li>
                <li><strong>Berichtigung (Art. 16):</strong> Unrichtige Daten können berichtigt werden.</li>
                <li><strong>Löschung (Art. 17):</strong> Sie können die Löschung Ihrer Daten verlangen („Recht auf
                    Vergessenwerden").
                    Ausnahme: Audit Logs und Verteilungsdaten werden aus Compliance-Gründen aufbewahrt.
                </li>
                <li><strong>Einschränkung (Art. 18):</strong> Sie können die Einschränkung der Verarbeitung verlangen.
                </li>
                <li><strong>Datenübertragbarkeit (Art. 20):</strong> Sie können Ihre Daten in strukturierter,
                    maschinenlesbarer Form erhalten.
                </li>
                <li><strong>Widerspruch (Art. 21):</strong> Sie können der Verarbeitung zu bestimmten Zwecken
                    widersprechen.
                </li>
                <li><strong>Beschwerde (Art. 77):</strong> Sie können bei der zuständigen Datenschutzbehörde Beschwerde
                    einreichen.
                </li>
            </ul>
            <p>Hierzu und zu weiteren Fragen können Sie sich jederzeit per E-Mail an
                <a href="mailto:{{ Cfg::get('email_admin_mail', 'email') }}">{{ Cfg::get('email_admin_mail', 'email') }}</a>
                wenden.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Widerruf der Einwilligung zur Datenverarbeitung</h2>
            <p>Bereits erteilte Einwilligungen können Sie jederzeit formlos per E-Mail widerrufen.
                Die Rechtmäßigkeit der bis zum Widerruf erfolgten Datenverarbeitung bleibt vom Widerruf unberührt.
                Ein Widerruf der Einwilligung zur Nutzung der Plattform führt zur Deaktivierung oder Löschung Ihres
                Kontos.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Datenschutzbeauftragter</h2>
            <p>Fragen zum Datenschutz und zur Geltendmachung Ihrer Rechte können Sie an den Datenschutzbeauftragten
                des Betreibers richten. Kontaktdaten finden Sie im <a href="{{ route('impressum') }}">Impressum</a>.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold mb-2">Änderungen dieser Datenschutzerklärung</h2>
            <p>Diese Datenschutzerklärung kann jederzeit ohne vorherige Ankündigung aktualisiert werden.
                Wesentliche Änderungen werden Ihnen per E-Mail oder durch prominente Benachrichtigung auf der Plattform
                mitgeteilt.
                Fortgesetzte Nutzung nach Änderungen bedeutet Akzeptanz der neuen Bedingungen.</p>
        </section>
    </div>
@endsection