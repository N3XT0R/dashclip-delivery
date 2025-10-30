@php use App\Facades\Cfg; @endphp
        <!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $fromBackend ? 'Dein Zugang zu ' . config('app.name') : 'Willkommen bei ' . config('app.name') }}</title>
</head>
<body style="font-family:Arial, sans-serif; background:#f8fafc; margin:0;">
@include('emails.partials.header')

<table width="100%" cellpadding="0" cellspacing="0"
       style="max-width:600px; margin:0 auto; background:#fff; border:1px solid #e2e8f0; border-radius:6px;">
    <tr>
        <td style="padding:24px; color:#0f172a; font-size:15px; line-height:1.6;">
            @if($fromBackend)
                <h1 style="font-size:20px; font-weight:700; margin:0 0 16px;">Dein Zugang
                    zu {{ config('app.name') }}</h1>
                <p>Hallo {{ $user->name }},</p>
                <p>dein Account wurde vom Team erstellt. Hier sind deine Zugangsdaten:</p>
                <ul style="margin:12px 0 18px 18px; padding:0;">
                    <li><strong>E-Mail:</strong> {{ $user->email }}</li>
                    @if($plainPassword)
                        <li><strong>Passwort:</strong> {{ $plainPassword }}</li>
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
                <a href="{{ route('filament.admin.auth.login') }}"
                   style="display:inline-block; background:#2563eb; color:#fff; padding:10px 18px;
                          text-decoration:none; border-radius:4px;">
                    Jetzt anmelden
                </a>
            </p>

            <p style="margin:24px 0 0 0;">Viele Grüße<br>Dein {{ config('app.name') }}-Team</p>
        </td>
    </tr>
</table>

@include('emails.partials.footer')
</body>
</html>
