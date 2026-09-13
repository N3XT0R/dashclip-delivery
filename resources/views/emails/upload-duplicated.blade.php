<x-email-layout :title="$subject ?? config('app.name')" :message="$message ?? null">
<h1 style="font-size:20px; font-weight:700; margin:0 0 16px;">
    Doppeleinsendung erkannt
</h1>

<p style="font-size:13px; color:#526171; margin:0 0 18px;">
    Stand: {{ $date->format('d.m.Y H:i') }} Uhr
</p>

<p>Hallo {{ $user->name }},</p>

<p>
    Dein Upload <strong>{{ $filename }}</strong> wurde verarbeitet,
    jedoch als <strong>Doppeleinsendung</strong> erkannt.
</p>

@if($note)
    <p>{{ $note }}</p>
@endif

<p style="margin-top:24px;">
    <x-email-button :url="route('filament.admin.auth.login')">Zum Dashboard</x-email-button>
</p>

<p style="margin:24px 0 0 0;">Viele Grüße<br>Dein {{ config('app.name') }}-Team</p>
</x-email-layout>
