<x-email-layout :title="$subject ?? config('app.name')" :message="$message ?? null">
@if($fromBackend)
    <h1 style="font-size:20px; font-weight:700; margin:0 0 16px;">Dein Zugang
        zu {{ config('app.name') }}</h1>
    <p>Hallo {{ $user->name }},</p>
    <p>dein Account wurde vom Team erstellt. Hier sind deine Zugangsdaten:</p>
    <ul style="margin:12px 0 18px 18px; padding:0;">
        <li><strong>E-Mail:</strong> {{ $user->email }}</li>
        @if($plainPassword)
            <li><strong>Passwort:</strong> <code>{{ $plainPassword }}</code></li>
        @endif
    </ul>
    <p>
        Bitte ändere dein Passwort nach dem ersten Login.
    </p>
@else
    <h1 style="font-size:20px; font-weight:700; margin:0 0 16px;">Willkommen, {{ $user->name }}!</h1>
    <p>Schön, dass du Teil von <strong>{{ config('app.name') }}</strong> bist.</p>
@endif

<p>
    <x-email-button :url="route('filament.admin.auth.login')">Jetzt anmelden</x-email-button>
</p>

<p style="margin:24px 0 0 0;">Viele Grüße<br>Dein {{ config('app.name') }}-Team</p>
</x-email-layout>
