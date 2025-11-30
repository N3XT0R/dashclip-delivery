@php use App\Facades\Cfg; @endphp
@extends('layouts.app')

@section('title', 'Datenschutz')

@section('content')
    <div class="panel">
        <h1 class="text-2xl font-bold mb-4">Datenschutzerklärung</h1>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Verantwortlicher</h2>
            <p>Verantwortlich für die Verarbeitung personenbezogener Daten ist die im
                <a href="{{ route('impressum') }}">Impressum</a> genannte Person.
            </p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Allgemeines</h2>
            <p>Der Schutz Ihrer personenbezogenen Daten hat hohe Priorität.
                Die nachfolgenden Hinweise erklären, welche Daten verarbeitet werden, zu welchen Zwecken dies geschieht
                und welche Rechte Ihnen zustehen.
                {{ config('app.name') }} ist eine Multi-Channel-Verteilungsplattform für Videoinhalte und verarbeitet
                Daten von Uploadern, Kanälen und Besuchern unterschiedlich.
            </p>
        </section>

        {{-- BENUTZERKONTEN --}}
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Benutzerkonten und Authentifizierung</h2>
            <p><strong>Uploader und Kanäle:</strong> Bei der Registrierung und Nutzung des Portals werden folgende Daten
                erhoben:</p>
            <ul class="list-disc list-inside mb-4">
                <li>E-Mail-Adresse</li>
                <li>Benutzername / Display-Name</li>
                <li>Verschlüsseltes Passwort</li>
                <li>MFA-Daten (TOTP-Geheimnisse, E-Mail-Codes)</li>
                <li>Registrierungsdatum und letzte Anmeldezeit</li>
                <li>Team-Zugehörigkeit</li>
            </ul>
            <p>Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung)
                sowie Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an Sicherheitsmaßnahmen wie MFA).
                Passwörter werden ausschließlich verschlüsselt gespeichert.
                MFA-Daten werden lokal verarbeitet und nicht an Dritte übermittelt.
                Die Speicherdauer entspricht der Dauer der Kontoverwaltung.
            </p>
        </section>

        {{-- VIDEO-UPLOAD --}}
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Video-Uploads und Metadaten</h2>
            <p><strong>Uploader:</strong> Beim Upload von Videos werden folgende Daten verarbeitet:</p>
            <ul class="list-disc list-inside mb-4">
                <li>Videodatei und Metadaten (Titel, Dauer, Dateiname)</li>
                <li>Eindeutige Hash-Kennungen zur Deduplizierung</li>
                <li>Zeitstempel des Uploads</li>
                <li>Benutzerkennung und Team-Zuordnung</li>
                <li>Automatisch generierte Video-Vorschau</li>
            </ul>
            <p>Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO.
                Die Daten dienen der Verwaltung, Distribution und Deduplizierung der Inhalte.
                Gelöschte Videos und Vorschauen werden vollständig entfernt.
            </p>
        </section>

        {{-- VERTEILUNG --}}
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Verteilung und Angebots-Verwaltung</h2>
            <p>Für die automatisierte Verteilung von Videos an Kanäle werden folgende Daten verarbeitet:</p>
            <ul class="list-disc list-inside mb-4">
                <li>Video–Kanal-Zuordnungen</li>
                <li>Angebotsstatus</li>
                <li>Versand und Empfang von Benachrichtigungen</li>
                <li>Zeitlich begrenzte, signierte Offer-Links</li>
                <li>Download-Tracking (Kanal-ID, Datum, Uhrzeit)</li>
                <li>Rückgabegründe</li>
            </ul>
            <p>
                Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO.
                Angebots- und Verteilungsdaten werden für Audit- und Compliance-Zwecke
                so lange aufbewahrt, wie dies für Nachweis- und Dokumentationspflichten erforderlich ist
                (in der Regel dauerhaft).
                Personenbezug wird soweit möglich pseudonymisiert.
            </p>
        </section>

        {{-- EMAIL --}}
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Benachrichtigungen und E-Mail-Kommunikation</h2>
            <p>Es werden automatisierte E-Mails versendet bei:</p>
            <ul class="list-disc list-inside mb-4">
                <li>Registrierung / Kanal-Erstellung</li>
                <li>E-Mail-Änderungen und Team-Freigaben</li>
                <li>Eingang von Video-Angeboten</li>
                <li>Erinnerungen vor Ablauf von Angeboten</li>
                <li>Upload-Ergebnissen</li>
            </ul>
            <p>
                Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO.
                E-Mail-Logs (Empfänger, Betreff, Zeitstempel, Status) werden 24 Monate gespeichert.
                In Testumgebungen können E-Mails an Test-Adressen umgeleitet werden.
            </p>
        </section>

        {{-- SERVER-LOGS --}}
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Server-Log-Dateien und technische Daten</h2>
            <p>Bei jedem Zugriff werden automatisch folgende Daten erfasst:</p>
            <ul class="list-disc list-inside mb-4">
                <li>IP-Adresse</li>
                <li>Datum und Uhrzeit</li>
                <li>Aufgerufene Seite / API-Endpunkt</li>
                <li>HTTP-Methode, Statuscode</li>
                <li>Browsertyp und Version</li>
            </ul>
            <p>
                Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO.
                Server-Logs werden nach 30 Tagen gelöscht, sofern keine gesetzlichen Pflichten entgegenstehen.
            </p>
        </section>

        {{-- ACTIVITY LOG --}}
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Activity Logging und Audit Trail</h2>
            <p>
                Es werden sicherheits- und funktionsrelevante Aktivitäten protokolliert
                (z. B. Logins, Uploads, Kanal-Auswahlen, Angebotsverwaltung,
                automatisierte Verteilungen, administrative Änderungen).
            </p>
            <p>
                Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO.
                Activity Logs werden 36 Monate gespeichert; sicherheitsrelevante Logs können länger aufbewahrt werden.
            </p>
        </section>

        {{-- COOKIES --}}
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Cookies und lokale Speicherung</h2>
            <p>
                Es werden ausschließlich technisch notwendige Cookies verwendet:
                ein Session-Cookie sowie ein Sicherheits-Cookie.
                Zusätzlich wird Ihre Theme-Einstellung lokal im Browser gespeichert.
                Es findet kein Tracking durch Drittanbieter statt.
            </p>
            <p>Rechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO.</p>
        </section>

        {{-- DROPBOX --}}
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Dropbox-Integration</h2>
            <p>
                Bei Aktivierung werden OAuth-Tokens gespeichert und zur Authentifizierung verwendet.
                Rechtsgrundlage: Art. 6 Abs. 1 lit. a DSGVO.
            </p>
            <p>
                Dropbox ist ein US-Anbieter; die Übertragung erfolgt auf Basis von
                Standardvertragsklauseln.
                Die Integration kann jederzeit deaktiviert werden.
            </p>
        </section>

        {{-- DOWNLOAD TRACKING --}}
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Download-Tracking</h2>
            <p>
                Beim Abruf von Offer-Links werden Kanal-ID, Video-ID sowie Datum und Uhrzeit
                protokolliert. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO.
            </p>
        </section>

        {{-- SPEICHERDAUER --}}
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Speicherdauer – Übersicht</h2>
            <ul class="list-disc list-inside mb-4">
                <li><strong>Benutzerkonten:</strong> bis zur Löschung</li>
                <li><strong>Videoinhalte:</strong> bis zur Entfernung</li>
                <li><strong>Angebots- und Verteilungsdaten:</strong> gemäß Audit-Erfordernissen, pseudonymisiert</li>
                <li><strong>E-Mail-Logs:</strong> 24 Monate</li>
                <li><strong>Activity Logs:</strong> 36 Monate</li>
                <li><strong>Server-Logs:</strong> 30 Tage</li>
            </ul>
        </section>

        {{-- SICHERHEIT --}}
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Datensicherheit</h2>
            <p>Es werden technische und organisatorische Maßnahmen nach dem Stand der Technik umgesetzt, u. a.:</p>
            <ul class="list-disc list-inside mb-4">
                <li>HTTPS-Verschlüsselung</li>
                <li>Sichere Passwortspeicherung</li>
                <li>Multi-Faktor-Authentifizierung</li>
                <li>Signierte Tokens mit Ablaufzeit</li>
                <li>Rollenbasierte Berechtigungen</li>
                <li>Activity Logging</li>
                <li>Regelmäßige Sicherheitsüberprüfungen</li>
            </ul>
            <p>
                Sicherheit kann nie vollständig gewährleistet werden.
                Verdächtige Vorgänge melden Sie bitte an
                <a href="mailto:{{ Cfg::get('email_admin_mail', 'email') }}">
                    {{ Cfg::get('email_admin_mail', 'email') }}
                </a>.
            </p>
        </section>

        {{-- DRITTE --}}
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Weitergabe an Dritte</h2>
            <p>Eine Weitergabe erfolgt nur, wenn:</p>
            <ul class="list-disc list-inside mb-4">
                <li>Sie eingewilligt haben,</li>
                <li>eine gesetzliche Pflicht besteht,</li>
                <li>dies zur Vertragserfüllung notwendig ist,</li>
                <li>oder Missbrauch verhindert werden muss.</li>
            </ul>
            <p>Videoinhalte werden ausschließlich an die von Ihnen ausgewählten Kanäle verteilt.</p>
        </section>

        {{-- RECHTE --}}
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Ihre Rechte</h2>
            <p>Sie haben nach DSGVO folgende Rechte:</p>
            <ul class="list-disc list-inside mb-4">
                <li>Auskunft</li>
                <li>Berichtigung</li>
                <li>Löschung</li>
                <li>Einschränkung der Verarbeitung</li>
                <li>Datenübertragbarkeit</li>
                <li>Widerspruch</li>
                <li>Beschwerde bei der Datenschutzbehörde</li>
            </ul>
            <p>Kontakt:
                <a href="mailto:{{ Cfg::get('email_admin_mail', 'email') }}">
                    {{ Cfg::get('email_admin_mail', 'email') }}
                </a>
            </p>
        </section>

        {{-- WIDERRUF --}}
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Widerruf der Einwilligung</h2>
            <p>
                Die Einwilligung zur Nutzung der Dropbox-Integration ist zwingend erforderlich,
                da die Plattform technisch auf der Speicherung und Verarbeitung der Inhalte über Dropbox basiert.
                Sie können Ihre Einwilligung jederzeit widerrufen; ein Widerruf führt jedoch dazu,
                dass Ihr Benutzerkonto deaktiviert wird und die Plattform nicht weiter genutzt werden kann.
                Die bis zum Widerruf erfolgte Verarbeitung bleibt rechtmäßig.
            </p>
        </section>

        {{-- ÄNDERUNGEN --}}
        <section>
            <h2 class="text-xl font-semibold mb-2">Änderungen dieser Datenschutzerklärung</h2>
            <p>
                Diese Datenschutzerklärung kann aktualisiert werden.
                Wesentliche Änderungen werden per E-Mail oder Plattformhinweis mitgeteilt.
            </p>
        </section>

    </div>
@endsection
