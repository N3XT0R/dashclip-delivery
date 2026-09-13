<x-email-layout :title="$subject ?? config('app.name')" :message="$message ?? null">
<h1 style="font-size:20px; font-weight:700; margin:0 0 16px;">
    Upload verarbeitet
</h1>

{{-- Datum / Zeit --}}
<p style="font-size:13px; color:#526171; margin:0 0 18px;">
    Stand: {{ $date->format('d.m.Y H:i') }} Uhr
</p>

<p>Hallo {{ $user->name }},</p>

<p>
    dein Upload wurde erfolgreich verarbeitet. Die Datei
    <strong>{{ $filename }}</strong> wurde vom System vollständig eingelesen und steht nun für die weiteren
    Schritte zur Verfügung.
</p>

@if(!empty($note))
    <p>
        <strong>Hinweis:</strong><br>
        <em>{{ $note }}</em>
    </p>
@endif

<p>
    Über den folgenden Link kannst du dich jederzeit anmelden, um die Daten einzusehen oder weitere Dateien
    hochzuladen.
</p>

<p style="margin:24px 0 0 0;">
    Viele Grüße<br>
    Dein {{ config('app.name') }}-Team
</p>
</x-email-layout>
