<x-email-layout :title="$subject ?? config('app.name')" :message="$message ?? null">
<h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700;">
    {{ $subject ?? 'Automatische Antwort – bitte nicht direkt antworten' }}
</h1>

<div style="margin:0 auto; text-align:left;">
    <x-page slug="email_faq"/>
</div>

<p style="margin:24px 0 0 0;">Viele Grüße<br>Dein {{ config('app.name') }}-Team</p>
</x-email-layout>
