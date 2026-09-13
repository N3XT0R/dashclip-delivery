<x-email-layout :title="$subject ?? config('app.name')" :message="$message ?? null">
<h1 style="margin:0 0 16px 0; font-size:20px; line-height:1.2; font-weight:700;">
    Neue Videos verfügbar
</h1>

<p style="margin:0 0 16px 0;">
    Hallo {{ $channel->creator_name ?: 'Liebes Team' }} ({{ $channel->name }}),
</p>

<p style="margin:0 0 12px 0;">
    für dich stehen neue Dashcam-Aufnahmen bereit (Batch #{{ $batch->id }}).
    <strong>Du siehst diese Clips als Erster</strong> – nur wenn du sie nicht brauchst, kann sie später ein
    anderer Kanal erhalten.
    <strong>Ab sofort bekommst du nur Clips, die noch kein anderer Kanal hatte – technisch
        garantiert.</strong>
    So bleibt jede Vergabe fair und exklusiv.
</p>

<p style="margin:0 0 12px 0;">Klicke auf den Button, um:</p>
<ul style="margin:0 0 20px 24px; padding:0;">
    <li style="margin:0 0 8px 0;"><strong>alle verfügbaren Videos</strong> mit Vorschau zu sehen</li>
    <li style="margin:0 0 8px 0;"> optional <strong>eine ZIP-Datei mit ausgewählten Clips</strong>
        herunterzuladen
    </li>
</ul>

{{-- Button --}}
<p><x-email-button :url="$offerUrl">Zu den Videos</x-email-button></p>

<p style="margin:0 0 12px 0;">
    <strong>Gültig bis:</strong> {{ $expiresAt->timezone(config('app.timezone'))->format('d.m.Y, H:i') }}
</p>
@if($isChannelOperator === false)
    <p style="margin:0 0 16px 0;">
        <a href="{{ $unusedUrl }}" target="_blank" style="color:#9a3412; text-decoration:underline;">
            Willst du diese Videos nicht verwenden? Sei so fair und gib sie zurück
        </a>
        – so können andere Kanäle profitieren und das Material nutzen.
    </p>
@endif
<hr style="border:none; border-top:1px solid #dce2e8; margin:20px 0;">

<p style="margin:0 0 24px 0;">
    Viele Grüße<br>Dein {{ config('app.name') }}-Team
</p>
</x-email-layout>
