@php use App\Facades\Cfg; @endphp
@extends('layouts.app')

@section('title', 'Nutzungsbedingungen')

@section('content')
    <div class="panel">
        <h1 class="text-2xl font-bold mb-4">Nutzungsbedingungen</h1>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Geltungsbereich</h2>
            <p>Diese Nutzungsbedingungen regeln die Nutzung des Online-Dienstes
                <strong>{{ config('app.name') }}</strong>.
                Mit dem Aufrufen, Verwenden oder sonstigen Zugriff auf diesen Dienst erklären Sie sich mit diesen
                Bedingungen einverstanden. Wenn Sie diesen Bedingungen nicht zustimmen, dürfen Sie den Dienst nicht
                nutzen.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Leistungsbeschreibung</h2>
            <p>{{ config('app.name') }} stellt registrierten <strong>Kanälen</strong> Videoinhalte über eine
                geschützte Weboberfläche zur Verfügung. Ein Anspruch auf dauerhafte Verfügbarkeit oder fehlerfreien
                Betrieb
                des Dienstes besteht nicht. Änderungen, Erweiterungen oder Einschränkungen des Angebots können jederzeit
                ohne vorherige Ankündigung erfolgen.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Zulässige Nutzung</h2>
            <p>Die bereitgestellten Inhalte dürfen ausschließlich im Rahmen der vorgesehenen Nutzung durch den
                jeweiligen
                Kanal verwendet werden. Eine Weitergabe an Dritte, Vervielfältigung oder öffentliche Verbreitung ohne
                ausdrückliche Zustimmung des Betreibers ist untersagt.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Pflichten der Kanäle</h2>
            <ul class="list-disc list-inside">
                <li>Kanäle verpflichten sich, Zugangsdaten vertraulich zu behandeln und nicht an Dritte weiterzugeben.
                </li>
                <li>Die Nutzung des Dienstes darf nicht zu rechtswidrigen Zwecken erfolgen.</li>
                <li>Bei Verdacht auf Missbrauch oder unbefugten Zugriff ist der Betreiber unverzüglich zu informieren.
                </li>
            </ul>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Urheberrechte</h2>
            <p>Alle Inhalte (insbesondere Videos, Texte, Logos und Layouts) unterliegen dem Urheberrecht und weiteren
                Schutzrechten. Jegliche Nutzung über den vorgesehenen Zweck hinaus bedarf der vorherigen schriftlichen
                Zustimmung des Rechteinhabers.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Haftungsausschluss</h2>
            <p>Der Betreiber übernimmt keine Gewähr für die Richtigkeit, Vollständigkeit oder Aktualität der
                bereitgestellten
                Inhalte. Jegliche Haftung für Schäden, die aus der Nutzung oder Nichtverfügbarkeit des Dienstes
                entstehen,
                ist ausgeschlossen, soweit gesetzlich zulässig.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Datenschutz</h2>
            <p>Informationen zur Verarbeitung personenbezogener Daten finden Sie in der
                <a href="{{ route('datenschutz') }}">Datenschutzerklärung</a>.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Einwilligung zur Nutzung</h2>
            <p>Mit der Nutzung des Dienstes erklären Sie sich ausdrücklich mit diesen Nutzungsbedingungen sowie der
                <a href="{{ route('datenschutz') }}">Datenschutzerklärung</a> einverstanden.
                Die Einwilligung erfolgt konkludent durch Nutzung des Angebots und kann jederzeit durch Beendigung der
                Nutzung widerrufen werden.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Kontakt</h2>
            <p>Fragen zu diesen Nutzungsbedingungen können Sie jederzeit per E-Mail an
                <a href="mailto:{{ Cfg::get('email_admin_mail', 'email') }}">{{ Cfg::get('email_admin_mail', 'email') }}</a>
                richten.</p>
        </section>

        <section>
            <h2 class="text-xl font-semibold mb-2">Schlussbestimmungen</h2>
            <p>Es gilt das Recht der Bundesrepublik Deutschland. Sollten einzelne Bestimmungen dieser
                Nutzungsbedingungen
                unwirksam sein, bleibt die Gültigkeit der übrigen Bestimmungen unberührt.</p>
        </section>
    </div>
@endsection
